jQuery(function($){
  'use strict';

  // Fallback for i18n object if the script loads before localization
  var i18n = window.cpdfvAdminI18n || {
    selectPdf: 'Select PDF File', selectCover: 'Select Cover Image', copied: 'Copied!',
    copyBtn: 'Copy Shortcode', pasteFirst: 'Paste a shortcode first.', invalidFormat: 'Invalid shortcode.',
    attrSetSingle: '1 attribute set', attrSetPlural: '%d attributes set',
    importSingle: 'Imported 1 attribute.', importPlural: 'Imported %d attributes.',
    shortcodeTag: 'continuous_pdf_viewer'
  };

  function setImportFeedback(message, type) {
    var $fb = $('#cpdfv-import-feedback');
    $fb.removeClass('is-success is-error').text(message || '');
    if (type) { $fb.addClass(type === 'success' ? 'is-success' : 'is-error'); }
  }

  function resetFormToDefaults() {
    $('[data-attr]').each(function() {
      var $el = $(this), attr = $el.data('attr'), def = DEFAULTS[attr] || '';
      if ($el.is(':checkbox')) {
        $el.prop('checked', def === 'yes');
      } else if ($el.hasClass('cpdfv-pill')) {
        if (def === 'yes') $el.addClass('active'); else $el.removeClass('active');
      } else if ($el.hasClass('cpdfv-color')) {
        $el.val(def);
        try { $el.wpColorPicker('color', def || '#ffffff'); } catch(e) {}
        if (!def) $el.val('');
      } else {
        $el.val(def);
      }
    });
  }

  function parseShortcodeAttributes(shortcode) {
    var out = {};
    // HARDENING: Dynamically match the actual shortcode tag to prevent regex failures
    var regexPattern = '\\[' + i18n.shortcodeTag + '\\s+([\\s\\S]*?)\\]$';
    var m = shortcode.match(new RegExp(regexPattern, 'i'));
    
    if (!m) return null;
    
    var attrs = m[1], re = /(\w+)=("([^"]*)"|'([^']*)')/g, hit;
    while ((hit = re.exec(attrs)) !== null) {
      out[String(hit[1]).toLowerCase()] = hit[3] !== undefined ? hit[3] : hit[4];
    }
    return out;
  }

  function applyImportedAttributes(attrs) {
    var matched = 0;
    resetFormToDefaults();

    Object.keys(attrs).forEach(function(attr) {
      var value = attrs[attr];
      var $targets = $('[data-attr="' + attr + '"]');
      if (!$targets.length) return;

      $targets.each(function() {
        var $el = $(this);
        if ($el.hasClass('cpdfv-pill')) {
          $el.toggleClass('active', String(value).toLowerCase() === 'yes');
        } else if ($el.is(':checkbox')) {
          $el.prop('checked', String(value).toLowerCase() === 'yes');
        } else if ($el.hasClass('cpdfv-color')) {
          $el.val(value);
          try { $el.wpColorPicker('color', value || '#ffffff'); } catch(e) {}
          if (!value) $el.val('');
        } else {
          $el.val(value);
        }
      });
      matched++;
    });

    buildShortcode();
    return matched;
  }

  $('.cpdfv-color').wpColorPicker({
    change: function() { setTimeout(buildShortcode, 50); },
    clear: function() { setTimeout(buildShortcode, 50); }
  });

  $('#cpdfv-media-btn').on('click', function(e) {
    e.preventDefault();
    var frame = wp.media({ title: i18n.selectPdf, library: { type: 'application/pdf' }, multiple: false });
    frame.on('select', function() {
      $('#cpdfv_url').val(frame.state().get('selection').first().toJSON().url).trigger('input');
    });
    frame.open();
  });

  $('#cpdfv-cover-media-btn').on('click', function(e) {
    e.preventDefault();
    var frame = wp.media({ title: i18n.selectCover, library: { type: 'image' }, multiple: false });
    frame.on('select', function() {
      $('#cpdfv_cover_image').val(frame.state().get('selection').first().toJSON().url).trigger('input');
    });
    frame.open();
  });

  $(document).on('click', '.cpdfv-pill', function() {
    $(this).toggleClass('active');
    buildShortcode();
  });

  var DEFAULTS = {};
  $('[data-attr]').each(function() {
    var $el = $(this), attr = $el.data('attr');
    if ($el.is(':checkbox')) DEFAULTS[attr] = $el.data('default') || 'yes';
    else if ($el.hasClass('cpdfv-pill')) DEFAULTS[attr] = $el.data('default') || 'yes';
    else DEFAULTS[attr] = ($el.data('default') !== undefined) ? String($el.data('default')) : '';
  });

  function buildShortcode() {
    var parts = [], c = 0;
    $('[data-attr]').each(function() {
      var $el = $(this), attr = $el.data('attr'), val, def;
      
      if ($el.is(':checkbox')) {
        val = $el.is(':checked') ? 'yes' : 'no'; def = DEFAULTS[attr] || 'yes';
      } else if ($el.hasClass('cpdfv-pill')) {
        val = $el.hasClass('active') ? 'yes' : 'no'; def = DEFAULTS[attr] || 'yes';
      } else if ($el.hasClass('cpdfv-color')) {
        val = $el.val() || ''; def = DEFAULTS[attr] || '';
      } else {
        val = $.trim($el.val()); def = DEFAULTS[attr] || '';
      }
      
      // HARDENING: Strip internal double quotes from values to prevent shortcode attribute breakage
      if (typeof val === 'string') {
        val = val.replace(/"/g, '&quot;');
      }

      if (attr === 'url') {
        if (val) { parts.push(attr + '="' + val + '"'); c++; }
      } else if (val && val !== def) {
        parts.push(attr + '="' + val + '"'); c++;
      }
    });

    var finalShortcode = parts.length ? '[' + i18n.shortcodeTag + ' ' + parts.join(' ') + ']' : '[' + i18n.shortcodeTag + ' url=""]';
    $('#cpdfv-shortcode-output').val(finalShortcode);
    
    // i18n pluralization handling
    var countText = c === 1 ? i18n.attrSetSingle : i18n.attrSetPlural.replace('%d', c);
    $('#cpdfv-attr-count').text(countText);
  }

  $(document).on('input change', '[data-attr]', buildShortcode);

  $('#cpdfv-copy-btn').on('click', function() {
    var $b = $(this), ta = document.getElementById('cpdfv-shortcode-output');
    ta.select(); ta.setSelectionRange(0, 99999);
    
    // HARDENING: Clipboard API feature check
    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard.writeText(ta.value).then(function() {
        $b.addClass('copied').html('&#10003; ' + i18n.copied);
        setTimeout(function() {
          $b.removeClass('copied').html('<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;vertical-align:text-bottom"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg> ' + i18n.copyBtn);
        }, 2000);
      });
    } else {
        // Fallback for non-HTTPS dev environments
        document.execCommand('copy');
        $b.addClass('copied').html('&#10003; ' + i18n.copied);
        setTimeout(function() {
          $b.removeClass('copied').html('<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;vertical-align:text-bottom"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg> ' + i18n.copyBtn);
        }, 2000);
    }
  });

  $('#cpdfv-reset-btn').on('click', function() {
    resetFormToDefaults();
    buildShortcode();
    setImportFeedback('');
  });

  $('#cpdfv-import-btn').on('click', function() {
    var raw = $.trim($('#cpdfv-import-shortcode').val());
    if (!raw) {
      setImportFeedback(i18n.pasteFirst, 'error');
      return;
    }
    
    var parsed = parseShortcodeAttributes(raw);
    if (!parsed) {
      setImportFeedback(i18n.invalidFormat, 'error');
      return;
    }
    
    var matched = applyImportedAttributes(parsed);
    var importText = matched === 1 ? i18n.importSingle : i18n.importPlural.replace('%d', matched);
    setImportFeedback(importText, 'success');
  });

  $('#cpdfv-clear-import-btn').on('click', function() {
    $('#cpdfv-import-shortcode').val('');
    setImportFeedback('');
  });

  buildShortcode();
});
