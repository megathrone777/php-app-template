import Alpine from "alpinejs";
import AlpineFocus from "@alpinejs/focus";
import { Fancybox } from "@fancyapps/ui/dist/fancybox";
import { Carousel } from "@fancyapps/ui/dist/carousel";
import { Arrows } from "@fancyapps/ui/dist/carousel/carousel.arrows";
import { Lazyload } from "@fancyapps/ui/dist/carousel/carousel.lazyload";
import { Thumbs } from "@fancyapps/ui/dist/carousel/carousel.thumbs";

import { initOverrides } from "./alpine-overrides";

document.addEventListener("alpine:init", () => {
  window.Arrows = Arrows;
  window.Carousel = Carousel;
  window.Fancybox = Fancybox;
  window.Lazyload = Lazyload;
  window.Thumbs = Thumbs;

  Alpine.plugin([AlpineFocus]);
  initOverrides();
});

Alpine.start();
