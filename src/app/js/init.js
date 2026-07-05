document.addEventListener("alpine:init", () => {
  Alpine.prefix("data-js-");

  Alpine.magic("truncate", () => {
    return (string, count) => (string.length > count ? string.slice(0, count - 1) + "..." : string);
  });

  Alpine.magic("uuid", () => {
    return Math.random().toString(36).slice(2) + Date.now().toString(36);
  });
});
