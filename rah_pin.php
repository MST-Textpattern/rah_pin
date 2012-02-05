<?php

/**
 * Rah_pin plugin for Textpattern CMS
 *
 * @author Jukka Svahn
 * @date 2011-
 * @license GNU GPLv2
 * @link https://github.com/gocom/rah_pin
 *
 * Copyright (C) 2012 Jukka Svahn <http://rahforum.biz>
 * Licensed under GNU Genral Public License version 2
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

	if(@txpinterface == 'admin') {
		register_callback('rah_pin', 'admin_side', 'head_end');
	}

/**
 * Add CSS and JavaScript to the <head>
 */

	function rah_pin() {

		global $event;
		
		if($event != 'article')
			return;
		
		$id = !empty($GLOBALS['ID']) ? $GLOBALS['ID'] : gps('ID');
		
		$ids = !isset($_POST['Image']) && $id ? 
			fetch('Image', 'textpattern', 'ID', $id) : ps('Image');
		
		$ids = explode(',', $ids);
		
		foreach($ids as $key => $img) {
			
			$img = trim($img);
			
			if(!empty($img) && is_numeric($img)) {
				$sql[] = "'" . doSlash($img) . "'";
			}
		}
		
		$js = array();
		
		if(isset($sql)) {
			$rs = 
				safe_rows(
					'id, ext, thumbnail',
					'txp_image',
					'id in(' . implode(',' , $sql) .')'
				);
			
			foreach($rs as $a) {
				$js[] = $a['id'] . " : '" . imagesrcurl($a['id'], $a['ext'], $a['thumbnail']) . "'";
			}
		}
		
		$js = '{'.implode(',', $js).'}';

		echo <<<EOF
			
			<style type="text/css">
				.rah_pin_images {
					display: block;
					padding: 10px 0 0 0;
				}
				.rah_pin_images .rah_pin_image {
					width: 160px;
					height: 80px;
					background-repeat: no-repeat !important;
					background-position: 0 0;
				}
				.rah_pin_images a.rah_pin_delete {
					margin: 0 0 0 10px;
				}
				.rah_pin_actions {
					margin: 0 0 10px 0;
					font-size: 0.9em;
				}
				.rah_pin_images .rah_pin_image,
				#rah_pin_window {
					-moz-border-radius: 3px;
					-webkit-border-radius: 3px;
					-khtml-border-radius: 3px;
					border-radius: 3px;
				}
				#rah_pin_overlay {
					background: #fff;
					position: fixed;
					left: 0;
					top: 0;
					width: 100%;
					height: 100%;
					opacity: 1;
					z-index: 9998;
				}
				#rah_pin_iframe {
					width: 100%;
					height: 100%;
					display: none;
				}
				#rah_pin_window {
					position: fixed;
					top: 50px;
					left: 100px;
					right: 100px;
					bottom: 60px;
					z-index: 9999;
					background: #fff;
					padding: 5px;
					text-align:right;
					-moz-box-shadow: inset 0 -1px 0 rgba(255,255,255,0.8), 0 0 1px rgba(0,0,0,0.2), 0 0 3px rgba(0,0,0,0.1), 0 5px 15px rgba(0,0,0,0.3);
					-webkit-box-shadow: inset 0 -1px 0 rgba(255,255,255,0.8), 0 0 1px rgba(0,0,0,0.2), 0 0 3px rgba(0,0,0,0.1), 0 5px 15px rgba(0,0,0,0.3);
					-khtml-box-shadow: inset 0 -1px 0 rgba(255,255,255,0.8), 0 0 1px rgba(0,0,0,0.2), 0 0 3px rgba(0,0,0,0.1), 0 5px 15px rgba(0,0,0,0.3);
					-o-box-shadow: inset 0 -1px 0 rgba(255,255,255,0.8), 0 0 1px rgba(0,0,0,0.2), 0 0 3px rgba(0,0,0,0.1), 0 5px 15px rgba(0,0,0,0.3);
					box-shadow: inset 0 -1px 0 rgba(255,255,255,0.8), 0 0 1px rgba(0,0,0,0.2), 0 0 3px rgba(0,0,0,0.1), 0 5px 15px rgba(0,0,0,0.3);
				}
				#rah_pin_done {
					position: fixed;
					bottom: 0;
					right: 100px;
					z-index: 9999;
					height: 50px;
					display: none;
				}
			</style>
			<script type="text/javascript">
				<!--
				var rah_pin = 
					{
						unpin: 'Unpin',
						edit: 'Edit',
						field: '#article-image',
						add: 'Add',
						added: 'Pinned',
						done: 'Done',
						images: {$js}
					};
				
				/*
					Markup for a item
				*/
				
				function rah_pin_item(id, url) {
					
					var out = 
						'<div class="rah_pin_item rah_pin_item_'+id+'">'+
							'<div class="rah_pin_image" style="background-image: url(\''+url+'\');"></div>'+
							'<div class="rah_pin_actions">'+
								'<a target="_blank" href="?event=image&amp;step=image_edit&amp;id='+id+'" class="rah_pin_edit">'+rah_pin['edit']+'</a> '+
								'<a href="#" class="rah_pin_unpin" rel="'+id+'">'+rah_pin['unpin']+'</a>'+
							'</div>'+
						'</div>';
					
					return out;
				}
				
				/*
					Add the needed container
				*/
			
				function rah_pin_base() {
					
					$(rah_pin['field']).each(
						function() {
							$(this).hide();
							
							var name = $(this).attr('name');
							var out = '';
							var value = '';
							
							if(rah_pin['images']) {
								for(i in rah_pin['images']) {
									out += rah_pin_item(i, rah_pin['images'][i]);
								}
							}
							
							/*
								Remove dead image IDs
							*/
							
							$(this).val(rah_pin_join(rah_pin['images']));
							
							$(this).before(
								'<div class="rah_pin_images" id="rah_pin_f_'+name+'">'+
								'	<div class="rah_pin_list">'+out+'</div>'+
								'	<a class="rah_pin_add" href="?event=image">' + rah_pin['add'] + '</a>'+
								'</div>'
							);
						}
					);
				}
				
				/*
					Add an image
				*/
				
				function rah_pin_add(){
					$('.rah_pin_add').live('click',function(e){
						e.preventDefault();
						
						$('body').css({'overflow' : 'hidden'});
						
						var p = $(this).parent().parent();
						var iframeURL = $(this).attr('href');
						
						$('body').append(
							'<div id="rah_pin_overlay"></div>'+
							'<div id="rah_pin_window">'+
								'<iframe id="rah_pin_iframe" src="'+iframeURL+'" frameborder="0"></iframe>'+
							'</div>'+
							'<div id="rah_pin_done"><a href="#">'+rah_pin['done']+'</a></div>'
						);
						
						$('#rah_pin_iframe').load(
							function(){
								var f = $(this).contents();
							
								f.find('label[for="image-upload"], a.pophelp, #list .tag-build, #list .date, #list .category, #list .actions, #list .author, #list .multi-edit, .thumbnail-edit, .thumbnail-alter, input.publish').hide();
							
								var e1 = f.find('.upload-form');
								var e2 = f.find('#images_form table#list');
								var e3 = f.find('#image_navigation .prev-next');
							
								f.find('body').html(
							
									'<h1 style="border-bottom: 1px dotted #ddd; padding: 0 20px 20px 20px; text-align: center; margin: 0 0 20px 0;">Select image by checking checkboxes, or upload a new one'
							
								).append(e1).append(e2).append(e3);
								
							
								f.find('table#list thead th').append('<th>'+rah_pin['added']+'</th>');
								f.find('table#list tbody tr').append(
									'<td class="rah_pin_pinned">'+
										'<input type="checkbox" name="rah_pin_pinned[]" class="rah_pin_pinned" />'+
									'</td>'
								);
								
								/*
									Select pinned images
								*/
								
								for(i in rah_pin['images']) {
									var id = i;
									f.find('table#list .id').filter(
										function(){
											return $(this).text() == id;
										}
									).siblings('td.rah_pin_pinned').find('input.rah_pin_pinned').attr('checked', true);
								}
								
								f.find('input.rah_pin_pinned').click(
									function(){
										
										var id = $(this).parents('tr').find('td.id').text();
										
										if(this.checked){
											
											var imgURL = $(this).parents('tr').find('td.thumbnail img').attr('src');
										
											if(!imgURL) {
											
												$.ajax({
													url: '?event=image&step=image_edit&id=' + id,
													success:
														function(data) {
															rah_pin_populate(
																id,
																$(data).contents().find('img#image-fullsize').attr('src'),
																p
															);
														}
												});
												
												return;
											}
										}
										
										rah_pin_populate(id, imgURL, p);
									}
								);
							
								f.find('body').css(
									{
										'padding' : '20px'
									}
								);
								
								$('#rah_pin_iframe, #rah_pin_done').show();
							
							}
						);
						
						$('#rah_pin_done a, #rah_pin_overlay').click(
							function(e) {
								e.preventDefault();
								$('#rah_pin_overlay, #rah_pin_window, #rah_pin_done').remove();
								$('body').css({'overflow' : 'auto'});
							}
						);
					});
				}
				
				/*
					Remove an image when clicking unpin image
				*/
				
				function rah_pin_unpin() {
					$('.rah_pin_unpin').live('click',
						function(e) {
							e.preventDefault();
							
							rah_pin_populate(
								$(this).attr('rel'),
								'',
								$(this).parent().parent().parent().parent().parent()
							);
						}
					);
				}
				
				/*
					Populate the field with list of IDs.
				*/
				
				function rah_pin_populate(id, url, p) {
					
					/*
						Remove image
					*/
					
					if(rah_pin['images'][id]) {
						delete rah_pin['images'][id];
						p.find('.rah_pin_item_'+id).remove();
					}
					
					/*
						Add image
					*/
					
					else {
						rah_pin['images'][id] = url;
						p.find('.rah_pin_list').append(rah_pin_item(id, url));
					}
					
					/*
						Re-populate the field
					*/
					
					p.find('input[type="text"]').val(rah_pin_join(rah_pin['images']));
				}
				
				/*
					Join array keys
				*/
				
				function rah_pin_join(obj) {
					var ids = '';
					
					for(i in obj)
						ids += i + ',';
					
					return ids ? ids.slice(0, -1) : ids;
				}

				$(document).ready(function() {
					rah_pin_base();
					rah_pin_add();
					rah_pin_unpin();
				});
				-->
			</script>
EOF;
	}

?>