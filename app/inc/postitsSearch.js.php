<?php

  require_once (__DIR__.'/../prepend.php');

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
          const val = this.value.trim ();

          if (val.length < 3)
            return plugin.reset ();

          plugin.search (val)
        })
        .on("keypress", function (e)
        {
          if (e.which == 13)
            plugin.element.modal ("hide");
        });
    },

    // METHOD open ()
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
      const $input = this.element.find ("input");

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
          .removeClass ("selected");

      $wall[0].removeAttribute ("data-searchstring");

      this.element.find(".result").empty ();
    },

    // METHOD search ()
    search (str)
    {
      const plugin = this,
            $search = plugin.element,
            $wall = S.getCurrent ("wall"),
            occur = {};

      plugin.reset ();

      $wall[0].dataset.searchstring = str;

      $wall.find(".postit-edit,"+
                 ".postit-header .title").not(":empty").each (
        function ()
        {
          const $edit = $(this);

          if ($edit.text().match (
            new RegExp (H.quoteRegex(str), 'ig')))
          {
            occur[$edit.closest(".postit").postit("getId")] = 1;

            $edit.closest(".postit").addClass ("selected");
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

<?php echo $Plugin->getFooter ()?>
