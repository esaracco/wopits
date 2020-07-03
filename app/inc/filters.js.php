<?php
  require_once (__DIR__.'/../class/Wpt_jQueryPlugins.php');
  $Plugin = new Wpt_jQueryPlugins ('filters');
  echo $Plugin->getHeader ();
?>

/////////////////////////// PUBLIC METHODS ////////////////////////////

  // Inherit from Wpt_toolbox
  Plugin.prototype = Object.create(Wpt_toolbox.prototype);
  Object.assign (Plugin.prototype,
  {
    // METHOD init ()
    init: function ()
    {
      const plugin = this,
            $filters = plugin.element,
            tList = $(".tag-picker").tagPicker ("getTagsList"),
            cList = $(".color-picker").colorPicker ("getColorsList");

      let tags = '';
      for (let i = 0, iLen = tList.length; i < iLen; i++)
      {
        const t = tList[i];

        tags +=
          `<div><i class="fa-${t} fa-fw fas" data-tag="${t}"></i></div>`;
      }

      let colors = '';
      for (let i = 0, iLen = cList.length; i < iLen; i++)
        colors += `<div class="${cList[i]}">&nbsp;</div>`;

      $filters
        //FIXME "distance" is deprecated -> is there any alternative?
        .draggable({distance:10})
        .resizable({
          handles: "all",
          autoHide: !$.support.touch
        })
        .append (`<button type="button" class="close" data-dismiss="modal"><span>&times;</span></button><h2><?=_("Filters")?></h2><div class="filters-items"><div class="tags"><h3><?=_("Tags:")?></h3>${tags}</div><div class="colors"><h3><?=_("Colors:")?></h3>${colors}</div></div>`);

      $filters.find(".close").on("click",
        function ()
        {
          plugin.hide ();            
        });

      $filters.find(".tags i").on("click",
        function (e)
        {
          $(this).parent().toggleClass ("selected");

          plugin.apply ();
        });

      $filters.find(".colors > div").on("click",
        function (e)
        {
          $(this).toggleClass ("selected");

          plugin.apply ();
        });
    },

    // METHOD hide ()
    hide: function ()
    {
      if (this.element.is (":visible"))
        $("#main-menu").find("li[data-action='filters'] a").trigger ("click");
    },

    hidePlugs: function ()
    {
      this.element.addClass ("plugs-hidden");
      wpt_sharer.getCurrent("wall").wall ("hidePostitsPlugs"); 
    },

    showPlugs: function ()
    {
      this.element.removeClass ("plugs-hidden");

      setTimeout (() =>
        wpt_sharer.getCurrent("wall").wall ("showPostitsPlugs"), 0);
    },

    // METHOD toggle ()
    toggle: function ()
    {
      const $filters = this.element,
            $wall = wpt_sharer.getCurrent ("wall");

      if ($filters.is (":visible"))
      {
        $filters
          .hide()
          .find(".tags div.selected,"+
                ".colors div.selected").removeClass ("selected");

        this.showPlugs ();
      }
      else
        $filters
          .css({top: "60px", left: "5px", display: "table"});

      this.apply ();
    },

    // METHOD reset ()
    reset: function ()
    {
      this.element.find(".selected").removeClass ("selected");

      this.apply ();
    },

    // METHOD apply ()
    apply: function ()
    {
      const plugin = this,
            $filters = plugin.element,
            $wall = wpt_sharer.getCurrent ("wall"),
            $postits = $wall.find(".postit"),
            $tags = $filters.find(".tags div.selected"),
            $colors = $filters.find(".colors div.selected");

      $wall.find(".postit")
        .removeClass("filter-display")
        .show ();

      if ($tags.length || $colors.length)
      {
        plugin.hidePlugs ();

        if ($tags.length)
          $tags.each (function ()
            {
              const tag =
                $(this).find("i").attr("class").match (/fa\-([^ ]+) /)[1];

              $wall.find(".postit[data-tags*=',"+tag+",']")
                .addClass("filter-display");
            });

        if ($colors.length)
          $colors.each (function ()
            {
              $wall.find(".postit."+this.className.split(" ")[0])
                .addClass("filter-display");
            });
 
        if ($wall.find(".postit.current:not(.filter-display)").length)
          $("#popup-layer").trigger ("click");

        $wall.find(".postit:not(.filter-display)").hide ();
      }
      else
        plugin.showPlugs ();
    }

  });

<?php echo $Plugin->getFooter ()?>
