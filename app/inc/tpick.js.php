<?php
/**
  Javascript plugin - Note tags

  Scope: Note
  Elements: .tpick
  Description: Manage notes tags
*/

  require_once (__DIR__.'/../prepend.php');

  $Plugin = new Wopits\jQueryPlugin ('tpick');
  echo $Plugin->getHeader ();

?>

  let _width = 0,
      _height = 0;

  /////////////////////////// PRIVATE METHODS ///////////////////////////

  // METHOD _getTagTemplate ()
  const _getTagTemplate = (tag)=>
    {
      return `<i class="fa-${tag} fa-fw fas" data-tag="${tag}"></i>`;
    };

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

      $picker.append (html);

      // EVENT "click" on tags
      $picker[0].addEventListener ("click", (e)=>
        {
          const el = e.target;

          e.stopImmediatePropagation ();

          if (el.tagName == 'I')
          {
            const div = el.parentNode,
                  select = !div.classList.contains ("selected"),
                  $postit = S.getCurrent ("postit"),
                  tag = el.dataset.tag;

            if (!select)
              $postit[0].querySelector(`.postit-tags i.fa-${tag}`).remove ();
            else
              $postit.find(".postit-tags").prepend (_getTagTemplate(tag));

            div.classList.toggle ("selected");

            plugin.refreshPostitDataTag ();

            const $f = S.getCurrent ("filters");
            if ($f.is (":visible"))
              $f.filters ("apply", {norefresh: true});
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
      return [<?='"'.join ('","', array_keys (WPT_MODULES['tpick']['items'])).'"'?>];
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
        $picker[0].querySelector(`i.fa-${tag.dataset.tag}`)
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
      const postit = $postit ? $postit[0] : S.getCurrent("postit")[0];
      let dataTag = "";

      postit.querySelectorAll(".postit-tags i").forEach (
        (tag) => dataTag += ','+tag.dataset.tag);

      if (dataTag)
        dataTag += ",";

      postit.dataset.tags = dataTag;

      if (dataTag)
        postit.querySelector(".postit-tags").style.display = "block";
      else
        postit.querySelector(".postit-tags").style.display = "none";
    },

    // METHOD getHTMLFromString ()
    getHTMLFromString (str)
    {
      if (!(str = str.replace(/(^,|,$)/g, "")))
        return "";

      const tags = str.split (",");
      let ret = "";

      for (const t of tags)
        ret += _getTagTemplate (t);

      return ret;
    }
  };

  /////////////////////////// AT LOAD INIT //////////////////////////////

  document.addEventListener ("DOMContentLoaded",
    ()=> setTimeout (()=> $("#tpick").tpick (), 0));

<?php echo $Plugin->getFooter ()?>
