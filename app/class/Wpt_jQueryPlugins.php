<?php

  class Wpt_jQueryPlugins
  {
    private $name, $defaultSettings;

    public function __construct ($name, $defaultSettings = '')
    {
      $this->name = $name;
      $this->defaultSettings = $defaultSettings;
    }

    public function getHeader ()
    {
      return <<<EOC
;(function ($, window, document, undefined)
{
  "use strict";

  const Plugin = function (element)
  {
    this.settings = {{$this->defaultSettings}};
  }
EOC;
    }

    public function getFooter ()
    {
      return <<<EOC
  $.fn["wpt_{$this->name}"] = function (arg)
  {
    if (!(this.data ("plugin_wpt_{$this->name}") instanceof Plugin))
      this.data ("plugin_wpt_{$this->name}", new Plugin (this));

    const plugin = this.data ("plugin_wpt_{$this->name}");

    plugin.element = this;

    if (!arg || typeof arg === "object")
    {
      $.extend (plugin.settings, arg);

      plugin.init (arg);
    }
    else if (typeof arg === "string" && typeof plugin[arg] === "function")
      return plugin[arg].apply (
               plugin, Array.prototype.slice.call (arguments, 1));
    else
      $.error("[wpt_{$this->name}] Method `"+arg+"` does not exist!");
  };

})(jQuery, window, document);
EOC;
    }
  }
