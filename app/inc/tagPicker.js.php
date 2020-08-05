<?php
  require_once (__DIR__.'/../class/Wpt_jQueryPlugins.php');
  $Plugin = new Wpt_jQueryPlugins ('tagPicker');
  echo $Plugin->getHeader ();
?>

  let _width = 0,
      _height = 0;

  /////////////////////////// PRIVATE METHODS ///////////////////////////

  // METHOD _getTagTemplate ()
  function _getTagTemplate (tag)
  {
    return `<i class="fa-${tag} fa-fw fas" data-tag="${tag}"></i>`;
  }

  /////////////////////////// PUBLIC METHODS ////////////////////////////

  Plugin.prototype =
  {
    // METHOD init ()
    init: function (args)
    {
      const plugin = this,
            $picker = plugin.element;
      let html = "";

      plugin.getTagsList().forEach (
        (tag) => html += `<div>${_getTagTemplate(tag)}</div>`);

      $picker.append(html)
        .on("click", function(e)
        {
          e.stopImmediatePropagation ();

          if (e.target.tagName == 'I')
          {
            const $div = $(e.target).parent (),
                  select = !$div.hasClass ("selected"),
                  $postit = S.getCurrent ("postit"),
                  tag = e.target.dataset.tag;

            if (!select)
              $postit.find(".postit-tags i.fa-"+tag).remove ();
            else
              $postit.find(".postit-tags").prepend (_getTagTemplate(tag));

            $div.toggleClass ("selected");

            plugin.refreshPostitDataTag ();

            S.getCurrent("filters").filters ("apply");
          }
        });

      H.waitForDOMUpdate (() =>
        {
          _width = $picker.outerWidth ();
          _height = $picker.outerHeight ();
        });
    },

    // METHOD getTagsList ()
    getTagsList: function ()
    {
      return [<?='"'.join ('","', array_keys (WPT_MODULES['tagPicker']['items'])).'"'?>];
    },

    // METHOD open ()
    open: function (args)
    {
      const plugin = this,
            $picker = plugin.element,
            $postit = S.getCurrent ("postit"),
            wW = $(window).outerWidth (),
            wH = $(window).outerHeight ();
      let x = args.pageX + 5,
          y = args.pageY - 20;

      $picker.find(".selected").removeClass ("selected");

      $postit.find(".postit-tags i").each (function ()
        {
          $picker.find ("i.fa-"+this.dataset.tag).parent()
            .addClass ("selected");
        });

      if (x + _width > wW)
        x = wW - _width - 20;

      if (y + _height > wH)
        y = wH - _height - 20;

      H.openPopupLayer (() =>
        {
          plugin.close ();
          S.getCurrent("postit").postit ("unedit");
        });

      $picker
        .css({top: y, left: x})
        .show ();
    },

    // METHOD close ()
    close: function ()
    { 
      if (this.element.length)
      {
        this.element.hide ();
        S.getCurrent("postit").trigger ("mouseleave");
      }
    },

    // METHOD refreshPostitDataTag ()
    refreshPostitDataTag: function ($postit)
    {
      let dataTag = "";

      if (!$postit)
        $postit = S.getCurrent ("postit");

      $postit.find(".postit-tags i").each (function ()
        {
          dataTag += ','+this.dataset.tag;
        });

      if (dataTag)
        dataTag += ",";

      $postit[0].dataset.tags = dataTag;

      if (dataTag)
        $postit.find(".postit-tags").show ();
      else
        $postit.find(".postit-tags").hide ();
    },

    // METHOD getHTMLFromString ()
    getHTMLFromString: function (str)
    {
      str = str.replace(/(^,|,$)/g, '');

      if (!str)
        return "";

      const tags = str.split (",");
      let ret = '';

      for (let i = 0, ilen = tags.length; i < ilen; i++)
        ret += _getTagTemplate (tags[i]);

      return ret;
    }

  };

  /////////////////////////// AT LOAD INIT //////////////////////////////

  $(function ()
    {
      const $plugin = $(".tag-picker");

      if ($plugin.length)
        $plugin.tagPicker ();
    });

<?php echo $Plugin->getFooter ()?>
