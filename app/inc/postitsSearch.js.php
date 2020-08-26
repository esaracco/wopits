<?php
  require_once (__DIR__.'/../class/Common.php');

  $Plugin = new Wopits\jQueryPlugin ('postitsSearch');
  echo $Plugin->getHeader ();
?>

/////////////////////////// PUBLIC METHODS ////////////////////////////

  Plugin.prototype =
  {
    // METHOD init ()
    init (args)
    {
      const plugin = this,
            $search = plugin.element;

      $search.find('input')
        .on("keyup", function (e)
        {
          const val = $(this).val().trim ();

          if (val.length < 3)
            return plugin.reset ();

          plugin.search ({str: val})
        });
    },

    open ()
    {
      H.openModal (this.element); 
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
      const $input = this.element.find("input");

      if ($input.val())
        $input.trigger ("keyup");
    },

    // METHOD reset ()
    reset (args)
    {
      const $wall = S.getCurrent ("wall");

      $wall
        .find(".postit-edit,"+
              ".postit-header .title").not(":empty").closest(".postit")
          .removeClass ("search-match");

      $wall[0].removeAttribute ("data-searchstring");

      this.element.find(".result").empty ();
    },

    // METHOD search ()
    search (args)
    {
      const plugin = this,
            $search = plugin.element,
            $wall = S.getCurrent ("wall"),
            occur = {};

      plugin.reset ();

      $wall[0].dataset.searchstring = args.str;

      $wall.find(".postit-edit,"+
                 ".postit-header .title").not(":empty").each (
        function ()
        {
          const $edit = $(this);


          if ($edit.text().match (
            new RegExp (H.quoteRegex(args.str), 'ig')))
          {
            const postitId = $edit.closest(".postit").postit("getId");

            occur[postitId] = 1;
            $edit.closest(".postit").addClass("search-match");
          }
        });

      const count = Object.keys(occur).length;

      $search.find(".result").html ((count) ?
        ((count == 1) ?
          "<?=_("1 sticky note match your search.")?>" :
          "<?=_("%s sticky notes match your search.")?>".replace("%s", count)) :
        "<?=_("No result")?>");
    }
  };

  /////////////////////////// AT LOAD INIT //////////////////////////////

  $(function ()
    {
      const $plugin = $("#postitsSearchPopup");

      if ($plugin.length)
        $plugin.postitsSearch ();
    });

<?php echo $Plugin->getFooter ()?>
