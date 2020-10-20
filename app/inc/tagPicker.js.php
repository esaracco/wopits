<?php

  require_once (__DIR__.'/../prepend.php');

  $Plugin = new Wopits\jQueryPlugin ('tagPicker');
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
    init (args)
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
    getTagsList ()
    {
      return [<?='"'.join ('","', array_keys (WPT_MODULES['tagPicker']['items'])).'"'?>];
    },

    // METHOD open ()
    open (args)
    {
      const plugin = this,
            $picker = plugin.element,
            $postit = S.getCurrent ("postit"),
            wW = $(window).outerWidth (),
            wH = $(window).outerHeight ();
      let x = args.pageX + 5,
          y = args.pageY - 20;

      $picker.find(".selected").removeClass ("selected");

      $postit[0].querySelectorAll(".postit-tags i").forEach ((tag)=>
        $picker[0].querySelector("i.fa-"+tag.dataset.tag)
          .parentNode.classList.add ("selected"));

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
    close ()
    { 
      if (this.element.length)
      {
        this.element.hide ();
        S.getCurrent("postit").trigger ("mouseleave");
      }
    },

    // METHOD refreshPostitDataTag ()
    refreshPostitDataTag ($postit)
    {
      const postit0 = $postit ? $postit[0] : S.getCurrent("postit")[0];
      let dataTag = "";

      postit0.querySelectorAll(".postit-tags i").forEach (
        (tag) => dataTag += ','+tag.dataset.tag);

      if (dataTag)
        dataTag += ",";

      postit0.dataset.tags = dataTag;

      if (dataTag)
        postit0.querySelector(".postit-tags").style.display = "block";
      else
        postit0.querySelector(".postit-tags").style.display = "none";
    },

    // METHOD getHTMLFromString ()
    getHTMLFromString (str)
    {
      if (!(str = str.replace(/(^,|,$)/g, '')))
        return "";

      const tags = str.split (",");
      let ret = '';

      for (const t of tags)
        ret += _getTagTemplate (t);

      return ret;
    }
  };

  /////////////////////////// AT LOAD INIT //////////////////////////////

  $(function ()
    {
      setTimeout (()=>
      {
        $("body").prepend (`<div id="tag-picker"></div>`);
        $("#tag-picker").tagPicker ();
      }, 0);
    });

<?php echo $Plugin->getFooter ()?>
