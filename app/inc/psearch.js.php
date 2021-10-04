<?php
/**
  Javascript plugin - Notes search

  Scope: Wall
  Elements: #psearchPopup
  Description: Search in wall's notes
*/

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
            search = plugin.element[0],
            input = search.querySelector ("input");

      _smPlugin = S.getCurrent("mmenu").mmenu ("getClass");

      // EVENT "keyup" on input
      input.addEventListener("keyup", (e)=>
        {
          const val = e.target.value.trim ();

          if (e.which == 13)
            return;

          if (val.length < 3)
            return plugin.reset ();

          plugin.search (val)
        });

      // EVENT "keypress" on input
      input.addEventListener ("keypress", (e)=>
         (e.which == 13) && plugin.close ());

        // EVENT "click" on input clear button
      search.querySelector(".clear-input").addEventListener ("click", (e)=>
        {
          plugin.reset (true)
          input.focus ();
        });

      // EVENT "hidden.bs.modal" on popup
      search.addEventListener ("hidden.bs.modal", (e)=>
        {
          if (_smPlugin.element.is (":visible"))
            setTimeout(()=> _smPlugin.showHelp (), 0);
        });
    },

    // METHOD open ()
    open ()
    {
      var tmp = S.getCurrent ("filters");
      if (tmp.is (":visible"))
        tmp.filters ("reset");

      H.openModal ({item: this.element});
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
      const input = this.element[0].querySelector ("input");

      input.value = str;
      input.dispatchEvent (new Event ("keyup"));
    },

    // METHOD replay ()
    replay ()
    {
      const input = this.element[0].querySelector ("input");

      input.value = S.getCurrent("wall")[0].dataset.searchstring||"";

      if (input.value)
        input.dispatchEvent (new Event ("keyup"));
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

      $wall.find(".postit-edit,.postit-header .title").not(":empty").each (
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
          `<?=_("1 note match your search.")?>` :
          `<?=_("%s notes match your search.")?>`.replace ("%s", count);
      else
        html = `<?=_("No result")?>`;

      $search.find(".result").html (html);
    }
  };

<?php echo $Plugin->getFooter ()?>
