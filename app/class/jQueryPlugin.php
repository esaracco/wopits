<?php

namespace Wopits;

class jQueryPlugin
{
  private $name, $defaultSettings, $type;

  public function __construct (string $name, string $defaultSettings = '',
                               string $type = '')
  {
    $this->name = $name;
    $this->defaultSettings = $defaultSettings;
    $this->type = $type;
  }

  public function getHeader ():string
  {
    $js = <<<EOC
;(function ($, window, document, undefined)
{
"use strict";

const Plugin = function (element)
{
  this.settings = {{$this->defaultSettings}};

  // METHOD getClass ()
  this.getClass = ()=> this;

  // METHOD getSettings ()
  this.getSettings = ()=> this.settings;
EOC;

    if ($this->type == 'wallElement')
    {
      $rw = WPT_WRIGHTS_RW;
      $adm = WPT_WRIGHTS_ADMIN;

      $js .= <<<EOC
  // METHOD getId ()
  this.getId  = ()=> this.settings.id;

  // METHOD canWrite ()
  this.canWrite = ()=>
  {
    const a = this.settings.access||S.getCurrent("wall")[0].dataset.access;
    return a == $rw || a == $adm;
  };
EOC;
    }

    return "$js}";
  }

  public function getFooter ():string
  {
    return <<<EOC
$.fn["{$this->name}"] = function (arg)
{
  if (!(this.data ("plugin_{$this->name}") instanceof Plugin))
    this.data ("plugin_{$this->name}", new Plugin (this));

  const plugin = this.data ("plugin_{$this->name}");

  plugin.element = this;

  if (!arg || typeof arg === "object")
  {
    $.extend (plugin.settings, arg);

    return plugin.init (arg);
  }
  else if (typeof arg === "string" && typeof plugin[arg] === "function")
    return plugin[arg].apply (
             plugin, Array.prototype.slice.call (arguments, 1));
  else
    $.error("[$this->name] Method `"+arg+"` does not exist!");
};

})(jQuery, window, document);
EOC;
  }
}
