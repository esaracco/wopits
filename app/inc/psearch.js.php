<?php

  require_once (__DIR__.'/../prepend.php');

  $Plugin = new Wopits\jQueryPlugin ('psearch');
  echo $Plugin->getHeader ();

?>

  let _smPlugin;

/////////////////////////// PUBLIC METHODS ////////////////////////////

  Plugin.prototype =
  {
    // METHOD init ()
    init (args)
    {
      const plugin = this,
            $search = plugin.element;

      _smPlugin = S.getCurrent("smenu").smenu ("getClass");

      $search.find("input")
        .on("keyup", function (e)
        {
          const val = this.value.trim ();

          if (val.length < 3)
            return plugin.reset ();

          plugin.search (val)
        })
        .on("keypress", function (e)
        {
          if (e.which == 13)
            plugin.close ();
        });

      // EVENT hidden.bs.modal on popups
      $search
        .on("hidden.bs.modal", function (e)
        {
          if (_smPlugin.element.is (":visible"))
            setTimeout(()=>  _smPlugin.showHelp (), 0);
        });
    },

    // METHOD open ()
    open ()
    {
      S.getCurrent("filters").filters ("reset");

      H.openModal (this.element);
      this.replay ();
    },

    // METHOD close ()
    close ()
    {
      this.element.modal ("hide");
    },

    // METHOD restore ()
    restore (str)
    {
      this.element.find("input").val (str);
      this.element.find("input").trigger ("keyup");
    },

    // METHOD replay ()
    replay ()
    {
      const $input = this.element.find ("input");

      $input.val (S.getCurrent("wall")[0].dataset.searchstring||'');

      if ($input.val())
        $input.trigger ("keyup");
      else
        this.reset ();
    },

    // METHOD reset ()
    reset (full)
    {
      const $search = this.element;

      if (full)
        $search.find("input").val ("");

      _smPlugin.reset ();
      S.getCurrent("wall")[0].removeAttribute ("data-searchstring");

      $search.find(".result").empty ();
    },

    // METHOD search ()
    search (str)
    {
      const plugin = this,
            $search = plugin.element,
            $wall = S.getCurrent ("wall");

      plugin.reset ();

      $wall[0].dataset.searchstring = str;

      $wall.find(".postit-edit,"+
                 ".postit-header .title").not(":empty").each (
        function ()
        {
          const $edit = $(this);

          if ($edit.text().match (new RegExp (H.quoteRegex(str), 'ig')))
            _smPlugin.add ($edit.closest(".postit").postit ("getClass"));
        });

      const count = $wall[0].querySelectorAll(".postit.selected").length;
      let html;

      if (count)
        html = (count == 1) ?
          "<?=_("1 note match your search.")?>" :
          "<?=_("%s notes match your search.")?>".replace ("%s", count);
      else
        html = "<?=_("No result")?>";

      $search.find(".result").html (html);
    }
  };

<?php echo $Plugin->getFooter ()?>
