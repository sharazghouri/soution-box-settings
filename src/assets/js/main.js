(function( $, document ) {

	var sbsa = {

		cache: function() {
			sbsa.els = {};
			sbsa.vars = {};

			sbsa.els.tab_links = $('.sbsa-nav__item-link');
			sbsa.els.submit_button = $( '.sbsa-button-submit' );
		},

		on_ready: function() {

			// on ready stuff here
			sbsa.cache();
			sbsa.trigger_dynamic_fields();
			sbsa.setup_groups();
			sbsa.tabs.watch();
			sbsa.watch_submit();
			sbsa.control_groups();
			sbsa.setup_visual_radio_checkbox_field();
			sbsa.importer.init();
			sbsa.misc.init();
			$( document.body ).on( 
				'change',
				'input, select, textarea, .sbsa-visual-field input[type="radio"], .sbsa-visual-field input[type="checkbox"]', 
				sbsa.control_groups
			);
		},

		/**
		 * Trigger dynamic fields
		 */
		trigger_dynamic_fields: function() {

			sbsa.setup_timepickers();
			sbsa.setup_datepickers();
			sbsa.setup_sortable();
		},

		/**
		 * Setup the main tabs for the settings page
		 */
		tabs: {
			/**
			 * Watch for tab clicks.
			 */
			watch: function() {
				var tab_id = sbsa.tabs.get_tab_id();

				if ( tab_id ) {
					sbsa.tabs.set_active_tab( tab_id );
				}

				sbsa.els.tab_links.on( 'click', function( e ) {
					// Show tab
					var tab_id = $( this ).attr( 'href' );

					sbsa.tabs.set_active_tab( tab_id );

					e.preventDefault();
				} );

				$( '.wsf-internal-link' ).click( sbsa.tabs.follow_link );
			},

			/**
			 * Is storage available.
			 */
			has_storage: 'undefined' !== typeof ( Storage ),
			
			/**
			 * Handle click on the Internal link.
			 * 
			 * Format of link is #tab-id|field-id. Field-id can be skipped.
			 * 
			 * @param {*} e
			 * @returns
			 */
			follow_link: function ( e ) {
				e.preventDefault();
				var href = $( this ).attr( 'href' );
				var tab_id, parts, element_id;

				if ( href.indexOf( '#tab-' ) != 0 ) {
					return;
				}

				// has "|" i.e. element ID.
				if ( href.indexOf( '|' ) > 0 ) {
					parts = href.split( '|' );
					tab_id = parts[ 0 ];
					element_id = parts[ 1 ];
				} else {
					tab_id = href;
				}

				sbsa.tabs.set_active_tab( tab_id );

				if ( element_id ) {
					$('html, body').animate({scrollTop: $(`#${element_id}`).offset().top - 100 }, 'fast');
				}
			},

			/**
			 * Store tab ID.
			 *
			 * @param tab_id
			 */
			set_tab_id: function( tab_id ) {
				if ( !sbsa.tabs.has_storage ) {
					return;
				}

				localStorage.setItem( sbsa.tabs.get_option_page() + '_sbsa_tab_id', tab_id );
			},

			/**
			 * Get tab ID.
			 *
			 * @returns {boolean}
			 */
			get_tab_id: function() {
				// If the tab id is specified in the URL hash, use that.
				if ( window.location.hash ) {
					// Check if hash is a tab.
					if ( $( `.sbsa-nav a[href="${window.location.hash}"]` ).length ) {
						return window.location.hash;
					}
				}

				if ( !sbsa.tabs.has_storage ) {
					return false;
				}

				return localStorage.getItem( sbsa.tabs.get_option_page() + '_sbsa_tab_id' );
			},

			/**
			 * Set active tab.
			 *
			 * @param tab_id
			 */
			set_active_tab: function( tab_id ) {
				var $tab = $( tab_id ),
					$tab_link = $( '.sbsa-nav__item-link[href="' + tab_id + '"]' );

				if ( $tab.length <= 0 || $tab_link.length <= 0 ) {
					// Reset to first available tab.
					$tab_link = $( '.sbsa-nav__item-link' ).first();
					tab_id = $tab_link.attr( 'href' );
					$tab = $( tab_id );
				}

				// Set tab link active class
				sbsa.els.tab_links.parent().removeClass( 'sbsa-nav__item--active' );
				$( 'a[href="' + tab_id + '"]' ).parent().addClass( 'sbsa-nav__item--active' );

				// Show tab
				$( '.sbsa-tab' ).removeClass( 'sbsa-tab--active' );
				$tab.addClass( 'sbsa-tab--active' );

				sbsa.tabs.set_tab_id( tab_id );
			},

			/**
			 * Get unique option page name.
			 *
			 * @returns {jQuery|string|undefined}
			 */
			get_option_page: function() {
				return $( 'input[name="option_page"]' ).val();
			}
		},

		/**
		 * Set up timepickers
		 */
		setup_timepickers: function() {

			$( '.timepicker' ).not( '.hasTimepicker' ).each( function() {

				var timepicker_args = $( this ).data( 'timepicker' );

				// It throws an error if empty string is passed.
				if ( '' === timepicker_args ) {
					timepicker_args = {};
				}

				$( this ).timepicker( timepicker_args );

			} );

		},

		/**
		 * Set up timepickers
		 */
		setup_datepickers: function() {
			$( document ).on( 'focus',  '.datepicker:not(.hasTimepicker)', function() {
				var datepicker_args = $( this ).data( 'datepicker' );

				// It throws an error if empty string is passed.
				if ( '' === datepicker_args ) {
					datepicker_args = {};
				}
				$( this ).datepicker( datepicker_args );
		
				
			} );

			// Empty altField if datepicker field is emptied.
			$( document ).on( 'change', '.datepicker', function(){
				var datepicker = $( this ).data( 'datepicker' );

				if ( ! $( this ).val() && datepicker.settings && datepicker.settings.altField ) {
					$( datepicker.settings.altField ).val( '' );
				}
			});
		},

		/**
		 * Set up sortable
		 */
		setup_sortable: function() {
			$( '.sbsa-sortable-list' ).sortable({
				cursor: "move",
				update: function( event, ui ) {

					const SortItem = $(ui.item).parent();
					const SortOrder = wp.hooks.applyFilters( 'sbsa_sort_list_update_value',$(SortItem ).sortable('toArray') );

					$(SortItem).parent().find('.sbsa-sortable-list-field').val(SortOrder.join(','));
					wp.hooks.doAction('sbsa_sort_list_update', SortItem, SortOrder); // use this Js Action hook to send ajax. It's totally upto you.
					
				}
			});

		},
		/**
		 * Setup repeatable groups
		 */
		setup_groups: function() {
			sbsa.reindex_groups();

			// add row

			$( document ).on( 'click', '.sbsa-group__row-add', function() {

				var $group = $( this ).closest( '.sbsa-group' ),
					$row = $( this ).closest( '.sbsa-group__row' ),
					template_name = $( this ).data( 'template' ),
					$template = $( $( '#' + template_name ).html() );

				$template.find( '.sbsa-group__row-id' ).val( sbsa.generate_random_id() );

				$row.after( $template );

				sbsa.reindex_group( $group );

				sbsa.trigger_dynamic_fields();

				return false;

			} );

			// remove row

			$( document ).on( 'click', '.sbsa-group__row-remove', function() {

				var $group = jQuery( this ).closest( '.sbsa-group' ),
					$row = jQuery( this ).closest( '.sbsa-group__row' );

				$row.remove();

				sbsa.reindex_group( $group );

				return false;

			} );

		},

		/**
		 * Generate random ID.
		 *
		 * @returns {string}
		 */
		generate_random_id: function() {
			return (
				Number( String( Math.random() ).slice( 2 ) ) +
				Date.now() +
				Math.round( performance.now() )
			).toString( 36 );
		},

		/**
		 * Reindex all groups.
		 */
		reindex_groups: function() {
			var $groups = jQuery( '.sbsa-group' );

			if ( $groups.length <= 0 ) {
				return;
			}

			$groups.each( function( index, group ) {
				sbsa.reindex_group( jQuery( group ) );
			} );
		},

		/**
		 * Reindex a group of repeatable rows
		 *
		 * @param arr $group
		 */
		reindex_group: function( $group ) {
			var reindex_attributes = [ 'class', 'id', 'name', 'data-datepicker' ];
			
			if ( 1 === $group.find( ".sbsa-group__row" ).length ) {
				$group.find( ".sbsa-group__row-remove" ).hide();
			} else {
				$group.find( ".sbsa-group__row-remove" ).show();
			}

			$group.find( ".sbsa-group__row" ).each( function( index ) {

				$( this ).removeClass( 'alternate' );

				if ( index % 2 == 0 ) {
					$( this ).addClass( 'alternate' );
				}

				$( this ).find( "input" ).each( function() {
					var this_input = this,
						name = jQuery( this ).attr( 'name' );

					if ( typeof name !== typeof undefined && name !== false ) {
						$( this_input ).attr( 'name', name.replace( /\[\d+\]/, '[' + index + ']' ) );
					}

					$.each( this_input.attributes, function() {
						if ( this.name && this_input && $.inArray( this.name, reindex_attributes ) > -1 ) {
							$( this_input ).attr( this.name, this.value.replace( /\_\d+\_/, '_' + index + '_' ) );
						}
					} );
				} );

				$( this ).find( '.sbsa-group__row-index span' ).html( index );

			} );
		},

		/**
		 * Watch submit click.
		 */
		watch_submit: function() {
			sbsa.els.submit_button.on( 'click', function() {
				var $button = $( this ),
					$wrapper = $button.closest( '.sbsa-settings' ),
					$form = $wrapper.find( 'form' ).first();

				$form.submit();
			} );
		},

		/**
		 * Dynamic control groups.
		 */
		control_groups: function() {
			// If show if, hide by default.
			$( '.show-if' ).each( function( index ) {
				var element = $( this ),
				    parent_tag = element.parent().prop( 'nodeName' ).toLowerCase();
				

				// Field.
				if ( 'td' === parent_tag || 'label' === parent_tag || sbsa.is_visual_field( element ) ) {
					element.closest( 'tr' ).hide();

					sbsa.maybe_show_element( element, function() {
						element.closest( 'tr' ).show();
					} );
				}

				// Tab.
				if ( 'li' === parent_tag ) {
					element.closest( 'li' ).hide();

					sbsa.maybe_show_element( element, function() {
						element.closest( 'li' ).show();
					} );
				}

				// Section.
				if ( 'div' === parent_tag && ! sbsa.is_visual_field( element ) ) {
					element.prev().hide();
					element.next().hide();
					if ( element.next().hasClass( 'sbsa-section-description' ) ) {
						element.next().next().hide();
					}

					sbsa.maybe_show_element( element, function() {
						element.prev().show();
						element.next().show();
						if ( element.next().hasClass( 'sbsa-section-description' ) ) {
							element.next().next().show();
						}
					} );
				}
			} );

			// If hide if, show by default.
			$( '.hide-if' ).each( function( index ) {
				var element = $( this ),
				    parent_tag = element.parent().prop( 'nodeName' ).toLowerCase();

				// Field.
				if ( 'td' === parent_tag || 'label' === parent_tag || sbsa.is_visual_field( element ) ) {
					element.closest( 'tr' ).show();

					sbsa.maybe_hide_element( element, function() {
						element.closest( 'tr' ).hide();
					} );
				}

				// Tab.
				if ( 'li' === parent_tag ) {
					element.closest( 'li' ).show();

					sbsa.maybe_hide_element( element, function() {
						element.closest( 'li' ).hide();
					} );
				}

				// Section.
				if ( 'div' === parent_tag && ! sbsa.is_visual_field( element ) ) {
					element.prev().show();
					element.next().show();
					if ( element.next().hasClass( 'sbsa-section-description' ) ) {
						element.next().next().show();
					}

					sbsa.maybe_hide_element( element, function() {
						element.prev().hide();
						element.next().hide();
						if ( element.next().hasClass( 'sbsa-section-description' ) ) {
							element.next().next().hide();
						}
					} );
				}
			} );
		},
		
		/**
		 * Is the element part of a visual field?
		 * 
		 * @param {object} element Element.
		 */
		is_visual_field: function( element ) {
			return element.parent().hasClass( 'sbsa-visual-field__item-footer' );
		},

		/**
		 * Maybe Show Element.
		 * 
		 * @param {object} element Element.
		 * @param {function} callback Callback.
		 */
		maybe_show_element: function( element, callback ) {
			var classes = element.attr( 'class' ).split( /\s+/ );
			var controllers = classes.filter( function( item ) {
				return item.includes( 'show-if--' );
			});

			Array.from( controllers ).forEach( function( control_group ) {
				var item = control_group.replace( 'show-if--', '' );
				if ( item.includes( '&&' ) ) {
					var and_group = item.split( '&&' );
					var show_item = true;
					Array.from( and_group ).forEach( function( and_item ) {
						if ( ! sbsa.get_show_item_bool( show_item, and_item ) ) {
							show_item = false;
						}
					});

					if ( show_item ) {
						callback();
						return;
					}
				} else {
					var show_item = true;
					show_item = sbsa.get_show_item_bool( show_item, item );

					if ( show_item ) {
						callback();
						return;
					}
				}
			});
		},

		/**
		 * Maybe Hide Element.
		 * 
		 * @param {object} element Element.
		 * @param {function} callback Callback.
		 */
		maybe_hide_element: function( element, callback ) {
			var classes = element.attr( 'class' ).split( /\s+/ );
			var controllers = classes.filter( function( item ) {
				return item.includes( 'hide-if--' );
			});

			Array.from( controllers ).forEach( function( control_group ) {
				var item = control_group.replace( 'hide-if--', '' );
				if ( item.includes( '&&' ) ) {
					var and_group = item.split( '&&' );
					var hide_item = true;
					Array.from( and_group ).forEach( function( and_item ) {
						if ( ! sbsa.get_show_item_bool( hide_item, and_item ) ) {
							hide_item = false;
						}
					});

					if ( hide_item ) {
						callback();
						return;
					}
				} else {
					var hide_item = true;
					hide_item = sbsa.get_show_item_bool( hide_item, item );

					if ( hide_item ) {
						callback();
						return;
					}
				}
			});
		},

		/**
		 * Get Show Item Bool.
		 * 
		 * @param {bool} show Boolean.
		 * @param {object} item Element.
		 * @returns {bool}
		 */
		get_show_item_bool: function( show = true, item ) {
			var split = item.split( '===' );
			var control = split[0];
			var values = split[1].split( '||' );
			var control_value = sbsa.get_controller_value( control, values );

			if ( ! values.includes( control_value ) ) {
				show = ! show;
			}

			return show;
		},

		/** 
		 * Return the control value.
		 */
		get_controller_value: function( id, values ) {
			var control = $( '#' + id );

			// This may be an image_radio field.
			if ( ! control.length && values.length ) {
				control = $( '#' + id + '_' + values[0] );
			}

			if ( control.length && ( 'checkbox' === control.attr( 'type' ) || 'radio' === control.attr( 'type' ) ) ) {
				control = ( control.is( ':checked' ) ) ? control : false;
			}

			var value = ( control.length ) ? control.val() : 'undefined';

			if ( typeof value === 'undefined' ) {
				value = '';
			}

			return value.toString();
		},

		/**
		 * Add checked class when radio button changes.
		 */
		setup_visual_radio_checkbox_field: function() {
			var checked_class = 'sbsa-visual-field__item--checked';

			$( document ).on( 'change', '.sbsa-visual-field input[type="radio"], .sbsa-visual-field input[type="checkbox"]', function() {
				var $this = $( this ),
					$list = $this.closest( '.sbsa-visual-field' ),
					$list_item = $this.closest( '.sbsa-visual-field__item' ),
					$checked = $list.find( '.' + checked_class ),
					is_multi_select = $list.hasClass( 'sbsa-visual-field--image-checkboxes' );

				if ( is_multi_select ) {
					if ( $this.prop( 'checked' ) ) {
						$list_item.addClass( checked_class );
					} else {
						$list_item.removeClass( checked_class );
					}
				} else {
					$checked.removeClass( checked_class );
					$list_item.addClass( checked_class );
				}

			} );
		},

		/**
		 * Import related functions.
		 */
		importer: {
			init: function () {

				$( '.sbsa-import__button' ).click( function () {
					$( this ).parent().find( '.sbsa-import__file_field' ).trigger( 'click' );
				} );

				$( ".sbsa-import__file_field" ).change( function ( e ) {
					$this = $( this );
					$td = $this.closest( 'td' );

					var file_field = $this.get( 0 ),
						settings = "",
						sbsa_import_nonce = $td.find( '.sbsa_import_nonce' ).val();
						sbsa_import_option_group = $td.find( '.sbsa_import_option_group' ).val();
					
					
					if ( 'undefined' === typeof file_field.files[ 0 ] ) {
						alert( sbsa_vars.select_file );
						return;
					}

					if ( ! confirm( 'Are you sure you want to overrid existing setting?' ) ) {
						return;
					}

					sbsa.importer.read_file_text( file_field.files[ 0 ], function ( content ) {
						try {
							JSON.parse( content );
							settings = content;
						} catch {
							settings = false;
							alert( sbsa_vars.invalid_file );
						}

						if ( !settings ) {
							return;
						}
						
						$td.find( '.spinner' ).addClass( 'is-active' );
						// Run an ajax call to save settings.
						$.ajax( {
							url: 'admin-ajax.php',
							type: 'POST',
							data: {
								action: 'sbsa_import_settings',
								settings: settings,
								option_group: sbsa_import_option_group,
								_wpnonce: sbsa_import_nonce
							},
							success: function ( response ) {
								if ( response.success ) {
									location.reload();
								} else {
									alert( sbsa_vars.something_went_wrong );
								}

								$td.find( '.spinner' ).removeClass( 'is-active' );
							}
						} );
					} );
				} );
			},

			/**
			 * Read File text.
			 *
			 * @param string   File input. 
			 * @param finction Callback function. 
			 */
			read_file_text( file, callback ) {
				const reader = new FileReader();
				reader.readAsText(file);
				reader.onload = () => {
				  callback(reader.result);
				};
			}
		},
		misc: {
			 init : function(){
					sbsa.misc.select_icons();
			 },
			  select_icons: function(){
				//Browse Icons
				$(document).on( 'click', '.sbsa-browse-icon', function( e ) {
					e.preventDefault();
		
					const ModelID = $(this).attr('data-model');
		
					$( `#${ModelID}` ).show();
				} );
		
				//Close Modal
				$( '.sbsa-modal-icon .close' ).on( 'click', function() {
					$( '.sbsa-modal-icon' ).hide();
					$( '.sbsa_search_icon' ).val( '' );
					$( '.sbsa_icon_ul li' ).show();
				} );
		
				//Close Modal
				$( window ).on( 'click', function( e ) {
					if ( e.target.classList.contains('sbsa-modal-icon') ) {
						$( '.sbsa-modal-icon' ).hide();
						$( '.sbsa_search_icon' ).val( '' );
						$( '.sbsa_icon_ul li' ).show();
					}
				} );
		
		
		
				function SbSearchData( input ) {
					
					let a, i, txtValue;
					const filter = $( input ).val().toUpperCase();
					const li = $( input ).parents( '.sbsa-modal-icon' ).find( '.icon-list-wrap li' );
					debugger
					for ( i = 0; i < li.length; i++ ) {
						a = li[ i ].getElementsByTagName( 'i' )[ 0 ];
		
						if ( a === null ) {
							a = li[ i ].getElementsByTagName( 'label' )[ 0 ];
							txtValue = a.innerHTML;
						} else {
							txtValue = a.getAttribute( 'data-icon' );
						}
		
						if ( txtValue.toUpperCase().indexOf( filter ) > -1 ) {
							li[ i ].style.display = '';
						} else {
							li[ i ].style.display = 'none';
						}
					}
				}
		
				function sbsa_debounce(fn, delay) {
					var timer = null;
					return function () {
						var context = this,
							args = arguments;
						clearTimeout(timer);
						timer = setTimeout(function () {
							fn.apply(context, args);
						}, delay);
					};
				}
				// Search icons
				$( '.sbsa_search_icon' ).on( 'keyup', function(e) {
					sbsa_debounce( SbSearchData( this ), 500);
				} );
				function SbSearchData( input ) {
					
					let a, i, txtValue;
					const filter = $( input ).val().toUpperCase();
					const li = $( input ).parents( '.sbsa-modal-icon' ).find( '.icon-list-wrap li' );
					
					for ( i = 0; i < li.length; i++ ) {
						a = li[ i ].getElementsByTagName( 'i' )[ 0 ];
		
						if ( a === null ) {
							a = li[ i ].getElementsByTagName( 'label' )[ 0 ];
							txtValue = a.innerHTML;
						} else {
							txtValue = a.getAttribute( 'data-icon' );
						}
		
						if ( txtValue.toUpperCase().indexOf( filter ) > -1 ) {
							li[ i ].style.display = '';
						} else {
							li[ i ].style.display = 'none';
						}
					}
				}
		
				// Add Icon
				$( '.sbsa-modal-icon' ).on( 'click', '.select-icon', function( e ) {
					e.preventDefault();
					const icon = $( this ).find( 'i' );
					const iconName = icon.attr( 'class' );
					//Repeater tab add icon
					$( this ).parents( '.sbsa-modal-icon' ).siblings( '.sbsa-icon-input' ).val( iconName );
					$( this ).parents( '.sbsa-modal-icon' ).siblings('.sbsa-icon-output').html( '<i class="' + iconName + '"></i><a href="#" class="sbsa-icon-remove">Remove</a>' );
					$( this ).parents( '.sbsa-modal-icon' ).hide();
					$( '.sbsa_search_icon' ).val( '' );
		
				} );
				// Remove Icon
				$(document).on( 'click', '.sbsa-icon-remove', function(e){
					e.preventDefault();
					$(this).parent().siblings('.sbsa-icon-input' ).val('');
					$(this).parent().html('');
				});
		
			}
			
		}
	};

	$( document ).ready( sbsa.on_ready );

}( jQuery, document ));
