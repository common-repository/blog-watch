<?php
/**
 * Plugin Name: Blog Watch
 * Plugin URI: http://biostall.com
 * Description: Keep an eye on what your competitors, similar sites and favourite blogs are writing about, right from your Dashboard.
 * Author: Steve Marks (BIOSTALL)
 * Author URI: http://biostall.com
 * Version: 1.0.0
 *
 * Copyright 2013 BIOSTALL ( email : info@biostall.com )
 * 
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; version 3 of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

add_action('wp_dashboard_setup', 'cbw_plugin_add_dashboard_widgets' );

function cbw_plugin_add_dashboard_widgets()
{
	wp_add_dashboard_widget('cbw_plugin_dashboard_widget', 'Blog Watch', 'cbw_plugin_dashboard_widget');	
}

function cbw_plugin_dashboard_widget()
{
	$feed_urls = array();
	$num = 5;
	
	$cbw_plugin_settings = get_option('cbw_plugin_settings');
	
	if( $cbw_plugin_settings !== FALSE && $cbw_plugin_settings != "" && isset($cbw_plugin_settings['urls']) && count($cbw_plugin_settings['urls']) > 0 )
	{
		$feed_urls = ( (isset($cbw_plugin_settings['urls'])) ? $cbw_plugin_settings['urls'] : array() );
		$num = ( (isset($cbw_plugin_settings['num']) && $cbw_plugin_settings['num'] != "") ? $cbw_plugin_settings['num'] : 5 );
	}
?>
<div id="cbw-configure-button" class="wp-editor-wrap hide-if-no-js" style="margin-bottom:15px">
	<a href="#" class="button cbw-configure" title="Configure Watched Blogs" style="padding-right:15px;"><div style="float:left; margin:0; padding:0; margin-top:-4px; margin-left:-8px; width:32px; height:32px; background:transparent url(<?php echo admin_url(); ?>/images/menu.png) no-repeat -209px -33px;"></div>Configure</a>
</div>
<div id="cbw-configure" style="display:none">
	
	<h4>Configure Watched Blogs</h4>
	<br />
	<div id="cbw-configure-errors" style="display:none; margin-bottom:15px; background-color:#FFF3F3; border:1px solid #900; padding:10px; font-weight:bold"></div>
	Enter the URL's of the blogs that you wish to watch:
	<div class="input-text-wrap" id="cbw-feed-urls" style="margin-top:7px; margin-bottom:5px;">
	<?php
		if( count($feed_urls) )
		{
			foreach($feed_urls as $feed_url)
			{
				echo '<input type="text" name="feed_urls[]" value="'.$feed_url.'" style="width:90%"> <a href="#" class="cbw-remove-url">-</a>';
			}
		}
		else
		{
			// echo a blank one ready for use
			echo '<input type="text" name="feed_urls[]" value="" style="width:90%"> <a href="#" class="cbw-remove-url">-</a>'; 
		}
	?>
	</div>
	<a href="#" class="button" id="cbw-add-url">+ Add URL</a>
	<br /><br />
	Show <input type="number" name="num" id="cbw-num" value="<?php echo $num; ?>" min="1" max="100" style="width:50px"> entries
	<br /><br />
	<em style="color:#999">Note: You can enter a direct link to the site's RSS/ATOM feed, or enter the normal website address. If you enter the normal website address we'll attempt to locate the RSS/ATOM feed on your behalf.</em>
	<br /><br />
	<p class="submit" align="right">
		<span id="save_loading" style="display:none"><img src="<?php echo admin_url(); ?>/images/wpspin_light.gif" alt="Loading..."></span>&nbsp;
		<a href="#" class="cancel">Cancel</a>&nbsp;
		<a href="#" class="button-primary">Update</a>
	</p>
	<div style="clear:both"></div>
</div>
<div id="cbw-live-feed">Loading...</div>

<script type="text/javascript">

	var saving = false;
	jQuery(document).ready(function($) 
	{
		// Show configuration panel
		$('#cbw_plugin_dashboard_widget').on('click', '.cbw-configure', function()
		{
			$('#cbw-configure-button').fadeOut('fast');
			$('#cbw-live-feed').fadeOut('fast', function()
			{
				$('#cbw-configure').fadeIn('fast');
				$('#cbw-feed-urls input[value=\'\']:first').focus();
			});
				
			return false;
		});
		
		$('#cbw_plugin_dashboard_widget').on('click', '#cbw-add-url', function()
		{
			$('#cbw-feed-urls').append('<input type="text" name="feed_urls[]" value="" style="width:90%; display:none">');
			$('#cbw-feed-urls input:last-child').fadeIn('fast', function()
			{
				$('#cbw-feed-urls').append(' <a href="#" class="cbw-remove-url">-</a>');
				$('#cbw-feed-urls input:last-child').focus();
			});
		});
		
		$('#cbw_plugin_dashboard_widget').on('click', '.cbw-remove-url', function()
		{
			var input_to_remove = $(this).prev();
			input_to_remove.fadeOut('fast', function()
			{
				input_to_remove.remove();
			});
			$(this).fadeOut('fast', function()
			{
				$(this).remove();
			});
		});
		
		$('#cbw_plugin_dashboard_widget').on('keydown', 'input[name=\'feed_urls[]\']', function()
		{
			$('input[name=\'feed_urls[]\']').css("backgroundColor", '#FFFFFF');
		});
		
		// Save configuration panel
		$('#cbw_plugin_dashboard_widget .submit a.button-primary').click(function()
		{
			if( !saving )
			{
				saving = true;
				$('#save_loading').show();
				$('#cbw-configure-errors').slideUp('fast');
				
				var feed_urls_array = new Array();
				
				$('input[name=\'feed_urls[]\']').each(function(i)
				{
					if( $(this).val() != "" )
					{
						var feed_url_to_add = $(this).val()
						if (feed_url_to_add.toLowerCase().indexOf("http://") == -1 && feed_url_to_add.toLowerCase().indexOf("https://") == -1)
						{
							feed_url_to_add = "http://" + feed_url_to_add
						}
						feed_urls_array.push(feed_url_to_add);
					}
				});
				
				var data = {
					action: 'cbw_save',
					feed_urls: feed_urls_array,
					num: $('#cbw-num').val()
				};
			
				$.post(ajaxurl, data, function(response) 
				{
					saving = false;
					$('#save_loading').hide();
					
					if (response != "")
					{
						if (response != "OK")
						{
							response = response.split("|");
							if (response[0] == "invalid")
							{
								var invalid_urls = response[1].split("^^^");
								
								$('#cbw-configure-errors').html( invalid_urls.length + " of the URL's entered weren't valid RSS/ATOM feeds, or didn't contain a &lt;meta&gt; link pointing to their RSS/ATOM feed.");
								$('#cbw-configure-errors').slideDown('fast');
								
								// one or more of the url's entered were invalid. Let's go highlight them
								$('input[name=\'feed_urls[]\']').each(function(i)
								{
									for ( var i in invalid_urls )
									{
										if ( $(this).val() == invalid_urls[i] )
										{
											// It's this one. Lets highlight it
											$(this).css("backgroundColor", "#FFF3F3")
										}
									}
								});
							}
						}
						else
						{
							var data = {
								action: 'cbw_get_feed'
							};
							
							$('#cbw-live-feed').html("Loading...");
						
							$.post(ajaxurl, data, function(response) 
							{
								if (response == "")
								{
									$('#cbw-live-feed').html("No blog entries found in any of the configured sites RSS/ATOM feeds.");
								}
								else
								{
									$('#cbw-live-feed').html(response);
								}
							});
		
							$('#cbw-configure').fadeOut('fast', function()
							{
								$('#cbw-configure-button').fadeIn('fast');
								$('#cbw-live-feed').fadeIn('fast');
							});
						}
					}
					else
					{
						// if no response came back it mean there are URLs entered
					}
				});
			}
			
			return false;
		});
		
		$('#cbw_plugin_dashboard_widget .submit a.cancel').click(function()
		{
			$('#cbw-configure').fadeOut('fast', function()
			{
				$('#cbw-configure-button').fadeIn('fast');
				$('#cbw-live-feed').fadeIn('fast');
			});
		});
		
	<?php
		$cbw_plugin_settings = get_option('cbw_plugin_settings');
	
		if( count($feed_urls) )
		{
	?>
		var data = {
			action: 'cbw_get_feed'
		};
		
		$('#cbw-live-feed').html("Loading...");
		
		$.post(ajaxurl, data, function(response) 
		{
			if (response == "")
			{
				$('#cbw-live-feed').html("No blog entries found in any of the configured sites RSS/ATOM feeds.");
			}
			else
			{
				$('#cbw-live-feed').html(response);
			}
		});
	<?php
		}
		else
		{
	?>
		$('#cbw-live-feed').html("Please click '<a href=\"#\" class=\"cbw-configure\">Configure</a>' and enter the sites/blogs that you wish to watch.");
	<?php
		}
	?>
	});
</script>
<?php
}

add_action('wp_ajax_cbw_save', 'cbw_save_callback');

function cbw_save_callback() 
{
	$invalid_urls = array();
	$valid_urls = array();
	
	// loop through URLs and a) validate b) get actual RSS/ATOM feed URL
	if( isset($_POST['feed_urls']) && count($_POST['feed_urls']) )
	{
		$existing_feed_urls = array();
		
		$cbw_plugin_settings = get_option('cbw_plugin_settings');
		
		if( $cbw_plugin_settings !== FALSE && $cbw_plugin_settings != "" && isset($cbw_plugin_settings['urls']) && count($cbw_plugin_settings['urls']) > 0 )
		{
			$existing_feed_urls = ( (isset($cbw_plugin_settings['urls'])) ? $cbw_plugin_settings['urls'] : array() );
		}
		
		foreach( $_POST['feed_urls'] as $feed_url )
		{
			$valid = false;
			
			if( in_array($feed_url, $existing_feed_urls) )
			{
				// This one was already in the settings. No need to check it again
				$valid = true;
			}
			else
			{
				$feed = @file_get_contents($feed_url);
				
				if( $feed !== FALSE && $feed != "" )
				{
					// The URL is valid. Now check if a page or an RSS/ATOM feed
					
					$xml = @simplexml_load_string($feed);
					
					if( $xml !== FALSE )
					{
						// The page returned XML. Now check for the two 
						if( count($xml) > 0 )
						{
							// If RSS
							if( isset($xml->channel))
							{
								$valid = true;
							}
							// If Atom
							if( isset($xml->entry))
							{
								$valid = true;
							}
						}
					}
					
					if( $valid === FALSE )
					{
						$rss_link = cbw_get_rss_location($feed, $feed_url);
						
						if( $rss_link !== FALSE )
						{
							// We've found a vlid link. Good.
							$valid = true;
							$feed_url = $rss_link;
						}
					}
				}
			}
			
			if( $valid === FALSE )
			{
				$invalid_urls[] = $feed_url;
			}
			else
			{
				$valid_urls[] = $feed_url;
			}
		}
	}

	if ( count($invalid_urls) == 0 )
	{
		// All good. Now lets save the URLs and update num
		$options = array();
		$options['urls'] = $valid_urls;
		
		$_POST['num'] = preg_replace('(\D+)', '', $_POST['num']);
		
		$options['num'] = ( (isset($_POST['num']) && $_POST['num'] != "" && $_POST['num'] >= 1 && $_POST['num'] <= 100) ? $_POST['num'] : 5 );
		
		update_option( 'cbw_plugin_settings', $options );
		
		echo 'OK';
	}
	else
	{
		echo 'invalid|'.implode("^^^", $invalid_urls);
	}
	
	die(); // This is required to return a proper result
}

// http://stackoverflow.com/questions/6968107/how-to-fetch-rss-feed-url-of-a-website-using-php
function cbw_get_rss_location($html, $location)
{
    if( !$html || !$location )
    {
        return false;
    }
    else
    {
        #search through the HTML, save all <link> tags
        # and store each link's attributes in an associative array
        preg_match_all('/<link\s+(.*?)\s*\/?>/si', $html, $matches);
        $links = $matches[1];
        $final_links = array();
        $link_count = count($links);
        for($n=0; $n<$link_count; $n++){
            $attributes = preg_split('/\s+/s', $links[$n]);
            foreach($attributes as $attribute){
                $att = preg_split('/\s*=\s*/s', $attribute, 2);
                if(isset($att[1])){
                    $att[1] = preg_replace('/([\'"]?)(.*)\1/', '$2', $att[1]);
                    $final_link[strtolower($att[0])] = $att[1];
                }
            }
            $final_links[$n] = $final_link;
        }
		
        #now figure out which one points to the RSS file
        for($n=0; $n<$link_count; $n++){
            if(strtolower($final_links[$n]['rel']) == 'alternate'){
                if(strtolower($final_links[$n]['type']) == 'application/rss+xml'){
                    $href = $final_links[$n]['href'];
                }
                if(!$href and strtolower($final_links[$n]['type']) == 'text/xml'){
                    #kludge to make the first version of this still work
                    $href = $final_links[$n]['href'];
                }
                if($href){
                    if(strstr($href, "http://") !== false){ #if it's absolute
                        $full_url = $href;
                    }else{ #otherwise, 'absolutize' it
                        $url_parts = parse_url($location);
                        #only made it work for http:// links. Any problem with this?
                        $full_url = "http://$url_parts[host]";
                        if(isset($url_parts['port'])){
                            $full_url .= ":$url_parts[port]";
                        }
                        if($href{0} != '/'){ #it's a relative link on the domain
                            $full_url .= dirname($url_parts['path']);
                            if(substr($full_url, -1) != '/'){
                                #if the last character isn't a '/', add it
                                $full_url .= '/';
                            }
                        }
                        $full_url .= $href;
                    }
                    return $full_url;
                }
            }
        }
        return false;
    }
}

add_action('wp_ajax_cbw_get_feed', 'cbw_get_feed_callback');

function cbw_get_feed_callback() 
{
   	$cbw_plugin_settings = get_option('cbw_plugin_settings');
	
	$num = 5;
	
	if( $cbw_plugin_settings !== FALSE && $cbw_plugin_settings != "" )
	{
		$errors = array();
		$items = array();
		
		$feed_urls = ( (isset($cbw_plugin_settings['urls'])) ? $cbw_plugin_settings['urls'] : array() );
		$num = ( (isset($cbw_plugin_settings['num'])) ? $cbw_plugin_settings['num'] : 5 );
		
		if( count( $feed_urls ) > 0 )
		{
			foreach( $feed_urls as $feed_url )
			{
				// At this point we should have a direct URL to the XML feed in RSS or ATOM form
				$feed = @file_get_contents($feed_url);
				
				if( $feed !== FALSE && $feed != "" )
				{
					$xml = @simplexml_load_string($feed);
					
					if( $xml !== FALSE )
					{
						if( count($xml) > 0 )
						{
							// If RSS
							if( isset($xml->channel->item) && count($xml->channel->item) > 0 )
							{
								$i = 0;
								foreach( $xml->channel->item as $item )
	       						{
	       							if ($i >= $num)
									{
										// No need to get anymore as  we've met the users option of $num
										break;
									}
									
									$items[strtotime($item->pubDate)] = '<li>
										<a class="rsswidget" href="' . $item->link . '" title="' . $item->description . '" target="_blank">' . $item->title . '</a>
										<br /><span class="rss-date" style="margin:0; padding:0">' . date("F d, Y", strtotime($item->pubDate)) . '</span>
										<div class="rssSummary">' . $item->description . '<br /><span style="color:#888">Source: ' . $feed_url . '</span></div>
									</li>';
									
									++$i;
								}
							}
							// If Atom
							if( isset($xml->entry) && count($xml->entry) > 0 )
							{
								$i = 0;
								
								foreach( $xml->entry as $item )
	       						{
	       							if ($i >= $num)
									{
										// No need to get anymore as  we've met the users option of $num
										break;
									}
									
									$itemLink = '';
									foreach ($item->link as $link) {
					                    $itemLink = $link['href'] . '';
					                    break;
					                }
									
									$items[strtotime($item->updated)] = '<li>
										<a class="rsswidget" href="' . $itemLink . '" title="' . $item->summary . '" target="_blank">' . $item->title . '</a>
										<br />
										<span class="rss-date">' . date("F d, Y", strtotime($item->updated)) . '</span>
										<div class="rssSummary">' . $item->summary . '<br /><span style="color:#888">Source: ' . $feed_url . '</span></div>
									</li>';
									
									++$i;
								}
							}
						}
					}
				}
				else
				{
					$errors[] = '<div class="error">The feed at '.$feed_url.' doesn\'t exist, has moved or is empty.</div>';
				}
			}
		}
	}
	
	if( count($errors) > 0 )
	{
		echo $errors;
	}
	
	if( count($items) > 0 )
	{
		// Sort the items by date (newest first) and just get top $num
		ksort($items); 
		$items = array_reverse($items);
		$items = array_slice($items, 0, $num);
		
		echo '<div class="rss-widget"><ul>' . implode("", $items) . '</ul></div>';
	}
	die(); // This is required to return a proper result
}
 
// Activation / Deactivation / Deletion
register_activation_hook( __FILE__, 'cbw_plugin_activation' );
register_deactivation_hook( __FILE__, 'cbw_plugin_deactivation' );

function cbw_plugin_activation()
{
	// Add option for storing plugin setting
	update_option( 'cbw_plugin_settings', array() );
	
	//register uninstaller
    register_uninstall_hook( __FILE__, 'cbw_plugin_uninstall' );
}

function cbw_plugin_deactivation()
{    
	// Actions to perform once on plugin deactivation go here
}

function cbw_plugin_uninstall()
{
	 delete_option('cbw_plugin_settings');
}