jQuery(function($){
  'use strict';

  function setImportFeedback(message,type){
    var $fb=$('#cpv-import-feedback');
    $fb.removeClass('is-success is-error').text(message||'');
    if(type){ $fb.addClass(type==='success' ? 'is-success' : 'is-error'); }
  }

  function resetFormToDefaults(){
    $('[data-attr]').each(function(){
      var $el=$(this),attr=$el.data('attr'),def=DEFAULTS[attr]||'';
      if($el.is(':checkbox')){
        $el.prop('checked',def==='yes');
      }else if($el.hasClass('cpv-pill')){
        if(def==='yes') $el.addClass('active'); else $el.removeClass('active');
      }else if($el.hasClass('cpv-color')){
        $el.val(def);
        try{ $el.wpColorPicker('color',def||'#ffffff'); }catch(e){}
        if(!def) $el.val('');
      }else{
        $el.val(def);
      }
    });
  }

  function parseShortcodeAttributes(shortcode){
    var out={}, m=shortcode.match(/\[pdf_viewer\s+([\s\S]*?)\]$/i);
    if(!m) return null;
    var attrs=m[1], re=/(\w+)=("([^"]*)"|'([^']*)')/g, hit;
    while((hit=re.exec(attrs))!==null){
      out[String(hit[1]).toLowerCase()] = hit[3]!==undefined ? hit[3] : hit[4];
    }
    return out;
  }

  function applyImportedAttributes(attrs){
    var matched=0;
    resetFormToDefaults();

    Object.keys(attrs).forEach(function(attr){
      var value=attrs[attr];
      var $targets=$('[data-attr="'+attr+'"]');
      if(!$targets.length) return;

      $targets.each(function(){
        var $el=$(this);
        if($el.hasClass('cpv-pill')){
          $el.toggleClass('active', String(value).toLowerCase()==='yes');
        }else if($el.is(':checkbox')){
          $el.prop('checked', String(value).toLowerCase()==='yes');
        }else if($el.hasClass('cpv-color')){
          $el.val(value);
          try{ $el.wpColorPicker('color', value || '#ffffff'); }catch(e){}
          if(!value) $el.val('');
        }else{
          $el.val(value);
        }
      });

      matched++;
    });

    buildShortcode();
    return matched;
  }

  $('.cpv-color').wpColorPicker({
    change:function(){setTimeout(buildShortcode,50)},
    clear:function(){setTimeout(buildShortcode,50)}
  });

  $('#cpv-media-btn').on('click',function(e){
    e.preventDefault();
    var frame=wp.media({title:'Select PDF File',library:{type:'application/pdf'},multiple:false});
    frame.on('select',function(){
      $('#cpv_url').val(frame.state().get('selection').first().toJSON().url).trigger('input');
    });
    frame.open();
  });

  $('#cpv-cover-media-btn').on('click',function(e){
    e.preventDefault();
    var frame=wp.media({title:'Select Cover Image',library:{type:'image'},multiple:false});
    frame.on('select',function(){
      $('#cpv_cover_image').val(frame.state().get('selection').first().toJSON().url).trigger('input');
    });
    frame.open();
  });

  $(document).on('click','.cpv-pill',function(){
    $(this).toggleClass('active');
    buildShortcode();
  });

  var DEFAULTS={};
  $('[data-attr]').each(function(){
    var $el=$(this),attr=$el.data('attr');
    if($el.is(':checkbox')) DEFAULTS[attr]=$el.data('default')||'yes';
    else if($el.hasClass('cpv-pill')) DEFAULTS[attr]=$el.data('default')||'yes';
    else DEFAULTS[attr]=($el.data('default')!==undefined)?String($el.data('default')):'';
  });

  function buildShortcode(){
    var parts=[],c=0;
    $('[data-attr]').each(function(){
      var $el=$(this),attr=$el.data('attr'),val,def;
      if($el.is(':checkbox')){
        val=$el.is(':checked')?'yes':'no';def=DEFAULTS[attr]||'yes';
      }else if($el.hasClass('cpv-pill')){
        val=$el.hasClass('active')?'yes':'no';def=DEFAULTS[attr]||'yes';
      }else if($el.hasClass('cpv-color')){
        val=$el.val()||'';def=DEFAULTS[attr]||'';
      }else{
        val=$.trim($el.val());def=DEFAULTS[attr]||'';
      }
      if(attr==='url'){
        if(val){parts.push(attr+'="'+val+'"');c++;}
      }else if(val&&val!==def){
        parts.push(attr+'="'+val+'"');c++;
      }
    });
    $('#cpv-shortcode-output').val(parts.length?'[pdf_viewer '+parts.join(' ')+']':'[pdf_viewer url=""]');
    $('#cpv-attr-count').text(c+' attribute'+(c!==1?'s':'')+' set');
  }

  $(document).on('input change','[data-attr]',buildShortcode);

  $('#cpv-copy-btn').on('click',function(){
    var $b=$(this),ta=document.getElementById('cpv-shortcode-output');
    ta.select();ta.setSelectionRange(0,99999);
    navigator.clipboard.writeText(ta.value).then(function(){
      $b.addClass('copied').html('&#10003; Copied!');
      setTimeout(function(){
        $b.removeClass('copied').html('<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg> Copy Shortcode');
      },2000);
    });
  });

  $('#cpv-reset-btn').on('click',function(){
    resetFormToDefaults();
    buildShortcode();
    setImportFeedback('');
  });

  $('#cpv-import-btn').on('click',function(){
    var raw=$.trim($('#cpv-import-shortcode').val());
    if(!raw){
      setImportFeedback('Paste a [pdf_viewer ...] shortcode first.','error');
      return;
    }
    var parsed=parseShortcodeAttributes(raw);
    if(!parsed){
      setImportFeedback('That does not look like a valid [pdf_viewer] shortcode.','error');
      return;
    }
    var matched=applyImportedAttributes(parsed);
    setImportFeedback('Imported '+matched+' attribute'+(matched!==1?'s':'')+'.','success');
  });

  $('#cpv-clear-import-btn').on('click',function(){
    $('#cpv-import-shortcode').val('');
    setImportFeedback('');
  });

  buildShortcode();
});
