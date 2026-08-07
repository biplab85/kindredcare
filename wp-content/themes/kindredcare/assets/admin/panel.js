/**
 * KindredCare — shared admin panel behaviour.
 *
 * Three jobs: the media picker, repeatable rows, and keeping a switched-off row
 * visibly switched off. Everything is delegated from the page root, so rows
 * cloned after load need no wiring of their own.
 *
 * Nothing here knows what it is repeating. A repeater declares its own option
 * key, its bounds and its counter text in markup, which is what lets the Hero
 * and Services screens share this file.
 */
( function () {
	'use strict';

	var root = document.querySelector( '[data-kc-admin]' );

	if ( ! root ) {
		return;
	}

	var config = window.kindredcarePanel || {};
	var strings = config.strings || {};

	/* -- Media picker ----------------------------------------------------- */

	var frame = null;

	function openPicker( trigger ) {
		var media = trigger.closest( '.kc-media' );
		var field = media.querySelector( '[data-kc-media-field]' );
		var image = media.querySelector( 'img' );

		// One frame for the whole screen: a new wp.media() per click leaks views.
		if ( ! frame ) {
			frame = wp.media( {
				title: strings.pickTitle || 'Choose an image',
				button: { text: strings.pickButton || 'Use this image' },
				library: { type: 'image' },
				multiple: false
			} );
		}

		// The handler closes over this row, so it must be rebound on each open.
		frame.off( 'select' );
		frame.on( 'select', function () {
			var attachment = frame.state().get( 'selection' ).first().toJSON();
			var sizes = attachment.sizes || {};
			var preview = sizes.medium || sizes.full || attachment;

			field.value = attachment.id;
			image.src = preview.url || attachment.url;
			media.classList.add( 'is-set' );
			setMediaReset( media, true );
		} );

		frame.open();
	}

	/**
	 * Enable the clear button only when there is a chosen image to clear.
	 *
	 * @param {Element} media   The .kc-media container.
	 * @param {boolean} hasImage Whether an attachment is now selected.
	 */
	function setMediaReset( media, hasImage ) {
		var reset = media.querySelector( '[data-kc-media-reset]' );

		if ( reset ) {
			reset.disabled = ! hasImage;
		}
	}

	function resetPicker( trigger ) {
		var media = trigger.closest( '.kc-media' );
		var field = media.querySelector( '[data-kc-media-field]' );
		var image = media.querySelector( 'img' );
		var fallback = field.getAttribute( 'data-kc-media-fallback' ) || '';

		field.value = '0';
		media.classList.remove( 'is-set' );
		setMediaReset( media, false );

		if ( fallback ) {
			image.src = fallback;
		} else {
			// Nothing to preview — the fallback is not an image (a step icon
			// falls back to an inline SVG), so the frame goes back to empty.
			image.removeAttribute( 'src' );
		}
	}

	/* -- Repeaters -------------------------------------------------------- */

	function repeaters() {
		return Array.prototype.slice.call( root.querySelectorAll( '[data-kc-repeat]' ) );
	}

	/**
	 * Find this repeater's own elements, ignoring any nested repeater's.
	 *
	 * Service lines are a repeater inside a card, which is itself a repeater
	 * row. A plain querySelector from the outer container would happily return
	 * the inner list's add button — it comes first in document order — and the
	 * outer counter would then start disabling the wrong control.
	 *
	 * @param {Element} repeat   The repeater container.
	 * @param {string}  selector What to look for.
	 * @param {Element} [scope]  Where to look; defaults to the repeater.
	 * @return {Element[]} Matches belonging to this repeater.
	 */
	function own( repeat, selector, scope ) {
		return Array.prototype.slice
			.call( ( scope || repeat ).querySelectorAll( selector ) )
			.filter( function ( node ) {
				return node.closest( '[data-kc-repeat]' ) === repeat;
			} );
	}

	function ownOne( repeat, selector, scope ) {
		return own( repeat, selector, scope )[ 0 ] || null;
	}

	function itemsOf( repeat ) {
		var list = ownOne( repeat, '[data-kc-list]' );
		return list ? Array.prototype.slice.call( list.children ) : [];
	}

	/**
	 * Renumber a repeater's rows and rewrite field names to match position.
	 *
	 * Names carry the index, so removing a row from the middle would otherwise
	 * leave a gap — PHP would receive 0, 1, 3 and the sanitiser's array_values()
	 * would silently reorder what survived.
	 */
	function reindex( repeat ) {
		var key = repeat.getAttribute( 'data-kc-repeat' );
		var namePattern = new RegExp( '\\[' + key + ']\\[\\d+]' );
		var rows = itemsOf( repeat );

		rows.forEach( function ( row, index ) {
			var num = row.querySelector( '.kc-repeat__num' );
			if ( num ) {
				num.textContent = String( index + 1 );
			}

			row.querySelectorAll( '[name]' ).forEach( function ( field ) {
				field.name = field.name.replace( namePattern, '[' + key + '][' + index + ']' );
			} );

			/*
			 * A row can contain its own nested repeater, and that repeater's
			 * <template> carries this row's index in its field names. Template
			 * content is a separate fragment, so the querySelectorAll above does
			 * not reach it — without this, removing a card would leave the
			 * surviving cards' "add service" templates writing into the array
			 * slot of the card that was deleted.
			 */
			row.querySelectorAll( 'template' ).forEach( function ( template ) {
				template.content.querySelectorAll( '[name]' ).forEach( function ( field ) {
					field.name = field.name.replace( namePattern, '[' + key + '][' + index + ']' );
				} );
			} );

			// id/for and id/aria-controls pairs must stay unique, or a label click
			// focuses the wrong row and a toggle opens someone else's panel.
			row.querySelectorAll( '[id]' ).forEach( function ( node ) {
				var next = node.id.replace( /-\d+-/, '-' + index + '-' );
				if ( next === node.id ) {
					return;
				}
				var label = row.querySelector( '[for="' + node.id + '"]' );
				var owner = row.querySelector( '[aria-controls="' + node.id + '"]' );
				node.id = next;
				if ( label ) {
					label.setAttribute( 'for', next );
				}
				if ( owner ) {
					owner.setAttribute( 'aria-controls', next );
				}
			} );
		} );

		repaintAutoColours( repeat, rows );
		updateControls( repeat, rows );
	}

	/**
	 * Switch a colour field between pinned and automatic.
	 *
	 * The saved value lives in the hidden input — empty means automatic. The
	 * swatch cannot express that, so it just shows whatever automatic currently
	 * resolves to, and the reset button is disabled rather than hidden so the
	 * row does not reflow every time the state changes.
	 *
	 * @param {Element} wrap   The .kc-colour container.
	 * @param {boolean} custom Whether a colour is now pinned.
	 * @param {string}  [hex]  The pinned colour, when custom.
	 */
	function setColourCustom( wrap, custom, hex ) {
		var field = wrap.querySelector( '[data-kc-colour-value]' );
		var swatch = wrap.querySelector( '[data-kc-colour-input]' );
		var reset = wrap.querySelector( '[data-kc-colour-reset]' );

		if ( custom ) {
			field.value = hex || swatch.value;
		} else {
			field.value = '';
			swatch.value = field.getAttribute( 'data-kc-colour-auto' ) || '#ffffff';
		}

		wrap.classList.toggle( 'is-custom', custom );

		if ( reset ) {
			reset.disabled = ! custom;
		}
	}

	/**
	 * Re-resolve each row's automatic colour after a reorder.
	 *
	 * The automatic tint is a function of position and cycles through the
	 * theme's palette, so removing a card shifts the tint of every card after
	 * it. Rows pinned to a custom colour are left alone.
	 *
	 * @param {Element}   repeat Repeater container.
	 * @param {Element[]} rows   Its rows, in order.
	 */
	function repaintAutoColours( repeat, rows ) {
		var raw = repeat.getAttribute( 'data-kc-palette' );

		if ( ! raw ) {
			return;
		}

		var palette;
		try {
			palette = JSON.parse( raw );
		} catch ( error ) {
			return;
		}

		if ( ! palette || ! palette.length ) {
			return;
		}

		rows.forEach( function ( row, index ) {
			var field = row.querySelector( '[data-kc-colour-value]' );
			var wrap = field && field.closest( '.kc-colour' );

			if ( ! field || ! wrap ) {
				return;
			}

			var auto = palette[ index % palette.length ];
			field.setAttribute( 'data-kc-colour-auto', auto );

			if ( ! wrap.classList.contains( 'is-custom' ) ) {
				var swatch = wrap.querySelector( '[data-kc-colour-input]' );
				if ( swatch ) {
					swatch.value = auto;
				}
			}
		} );
	}

	function updateControls( repeat, rows ) {
		var max = parseInt( repeat.getAttribute( 'data-kc-max' ), 10 ) || 99;
		var min = parseInt( repeat.getAttribute( 'data-kc-min' ), 10 ) || 1;
		var add = ownOne( repeat, '[data-kc-add]' );
		var counter = ownOne( repeat, '[data-kc-count]' );

		if ( add ) {
			add.disabled = rows.length >= max;
		}

		rows.forEach( function ( row ) {
			var remove = ownOne( repeat, '[data-kc-remove]', row );
			if ( remove ) {
				// Never let the last one go: an empty group has nothing to show.
				remove.disabled = rows.length <= min;
			}
		} );

		var active = rows.filter( function ( row ) {
			var toggle = ownOne( repeat, '[data-kc-toggle-row]', row );
			return toggle ? toggle.checked : true;
		} ).length;

		if ( counter ) {
			counter.textContent = ( counter.getAttribute( 'data-template' ) || '%1$s / %2$s / %3$s' )
				.replace( '%1$s', String( active ) )
				.replace( '%2$s', String( rows.length ) )
				.replace( '%3$s', String( max ) );
		}

		var below = ownOne( repeat, '[data-kc-warn-below]' );
		if ( below ) {
			below.hidden = active >= parseInt( below.getAttribute( 'data-kc-warn-below' ), 10 );
		}

		var above = ownOne( repeat, '[data-kc-warn-above]' );
		if ( above ) {
			above.hidden = active <= parseInt( above.getAttribute( 'data-kc-warn-above' ), 10 );
		}
	}

	function addRow( repeat ) {
		var max = parseInt( repeat.getAttribute( 'data-kc-max' ), 10 ) || 99;
		var rows = itemsOf( repeat );

		if ( rows.length >= max ) {
			return;
		}

		var template = ownOne( repeat, '[data-kc-template]' );
		if ( ! template ) {
			return;
		}

		// The template is emitted at index 0; reindex() gives it its real slot.
		var node = template.content.firstElementChild.cloneNode( true );
		ownOne( repeat, '[data-kc-list]' ).appendChild( node );

		reindex( repeat );

		// A new row is empty, so it opens — there is nothing to collapse yet.
		var toggle = node.querySelector( '[data-kc-acc-toggle]' );
		if ( toggle ) {
			setOpen( toggle, true );
		}

		node.scrollIntoView( { block: 'nearest', behavior: 'smooth' } );

		var first = node.querySelector( 'input[type="text"]' );
		if ( first ) {
			first.focus();
		}
	}

	function removeRow( repeat, trigger ) {
		var min = parseInt( repeat.getAttribute( 'data-kc-min' ), 10 ) || 1;

		if ( itemsOf( repeat ).length <= min ) {
			return;
		}

		trigger.closest( '[data-kc-item]' ).remove();
		reindex( repeat );
	}

	/* -- Accordion -------------------------------------------------------- */

	function setOpen( toggle, open ) {
		var body = document.getElementById( toggle.getAttribute( 'aria-controls' ) );
		var item = toggle.closest( '[data-kc-item]' );

		toggle.setAttribute( 'aria-expanded', String( open ) );

		if ( body ) {
			body.hidden = ! open;
		}

		// The chevron now sits outside the toggle, so it cannot be styled from
		// the button's own aria-expanded — the row carries the state instead.
		if ( item ) {
			item.classList.toggle( 'is-open', open );
		}
	}

	/**
	 * Mirror a row's name field into its collapsed header.
	 *
	 * Collapsed, the header is the only way to tell one row from another, so it
	 * cannot wait for a save to catch up.
	 */
	function syncTitle( field ) {
		var item = field.closest( '[data-kc-item]' );
		var label = item && item.querySelector( '[data-kc-acc-title]' );

		if ( label ) {
			label.textContent = field.value.trim();
		}
	}

	/* -- Wiring ----------------------------------------------------------- */

	root.addEventListener( 'click', function ( event ) {
		var target = event.target;

		/*
		 * The chevron sits at the far end of the header, after the remove
		 * button, so it cannot be part of the title button. It is a second way
		 * to hit the same control rather than a control of its own: hidden from
		 * assistive technology and out of the tab order, because the title
		 * button beside it already does the job and announcing two expanders
		 * for one panel is noise.
		 */
		var chevron = target.closest( '[data-kc-acc-chevron]' );
		if ( chevron ) {
			event.preventDefault();
			var owner = chevron.closest( '[data-kc-item]' ).querySelector( '[data-kc-acc-toggle]' );
			if ( owner ) {
				setOpen( owner, owner.getAttribute( 'aria-expanded' ) !== 'true' );
			}
			return;
		}

		var accordion = target.closest( '[data-kc-acc-toggle]' );
		if ( accordion ) {
			event.preventDefault();
			setOpen( accordion, accordion.getAttribute( 'aria-expanded' ) !== 'true' );
			return;
		}

		var picker = target.closest( '[data-kc-media-open]' );
		if ( picker ) {
			event.preventDefault();
			openPicker( picker );
			return;
		}

		var reset = target.closest( '[data-kc-media-reset]' );
		if ( reset ) {
			event.preventDefault();
			resetPicker( reset );
			return;
		}

		var add = target.closest( '[data-kc-add]' );
		if ( add ) {
			event.preventDefault();
			addRow( add.closest( '[data-kc-repeat]' ) );
			return;
		}

		var colourReset = target.closest( '[data-kc-colour-reset]' );
		if ( colourReset ) {
			event.preventDefault();
			setColourCustom( colourReset.closest( '.kc-colour' ), false );
			return;
		}

		var remove = target.closest( '[data-kc-remove]' );
		if ( remove ) {
			event.preventDefault();
			removeRow( remove.closest( '[data-kc-repeat]' ), remove );
		}
	} );

	root.addEventListener( 'input', function ( event ) {
		var field = event.target.closest( '[data-kc-acc-label]' );
		if ( field ) {
			syncTitle( field );
		}

		// Picking any colour pins the row; "automatic" is only ever restored by
		// the reset button, never by happening to land back on the same value.
		var swatch = event.target.closest( '[data-kc-colour-input]' );
		if ( swatch ) {
			setColourCustom( swatch.closest( '.kc-colour' ), true, swatch.value );
		}
	} );

	/**
	 * Dim a row whose switch is off, and grey a whole panel when its master
	 * switch is off — the state has to be visible without reading labels.
	 */
	root.addEventListener( 'change', function ( event ) {
		var toggle = event.target.closest( '[data-kc-toggle-row]' );

		if ( toggle ) {
			var row = toggle.closest( '.kc-row, .kc-repeat__item' );
			if ( row ) {
				row.classList.toggle( 'is-off', ! toggle.checked );
			}

			var repeat = toggle.closest( '[data-kc-repeat]' );
			if ( repeat ) {
				updateControls( repeat, itemsOf( repeat ) );
			}
		}

		var master = event.target.closest( '[data-kc-toggle-section]' );
		if ( master ) {
			var section = document.getElementById( master.getAttribute( 'data-kc-toggle-section' ) );
			if ( section ) {
				section.classList.toggle( 'is-off', ! master.checked );
			}
		}
	} );

	/* -- Saving ----------------------------------------------------------- */

	/*
	 * The form posts to options.php and the browser navigates away, so this
	 * state lasts until the reply comes back — which is exactly the window worth
	 * showing something for.
	 *
	 * The button is disabled a tick *after* submit, never before: disabling it
	 * synchronously would drop its name from the submitted data, and on a slow
	 * save a second click would otherwise post the form twice.
	 */
	var form = root.querySelector( 'form' );

	if ( form ) {
		form.addEventListener( 'submit', function () {
			var button = form.querySelector( '[data-kc-save]' );

			if ( ! button ) {
				return;
			}

			button.classList.add( 'is-saving' );
			button.setAttribute( 'aria-live', 'polite' );

			window.setTimeout( function () {
				button.disabled = true;
			}, 0 );
		} );

		/*
		 * Restored from the back/forward cache the page is served as it was —
		 * including a button frozen mid-save, which would then refuse to submit.
		 */
		window.addEventListener( 'pageshow', function ( event ) {
			if ( ! event.persisted ) {
				return;
			}
			var button = form.querySelector( '[data-kc-save]' );
			if ( button ) {
				button.classList.remove( 'is-saving' );
				button.disabled = false;
			}
		} );
	}

	repeaters().forEach( reindex );
}() );
