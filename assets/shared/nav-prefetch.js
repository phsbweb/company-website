(() => {
  const prefetched = new Set();

  function canUsePrefetch() {
    if (!("PointerEvent" in window)) return false;
    if (navigator.connection?.saveData) return false;
    return true;
  }

  function canPrefetch(link) {
    if (!link || !link.href) return false;
    if (link.target && link.target !== "_self") return false;
    if (link.hasAttribute("download")) return false;

    const url = new URL(link.href, window.location.href);
    if (url.origin !== window.location.origin) return false;
    if (url.protocol !== "http:" && url.protocol !== "https:") return false;
    if (url.pathname === window.location.pathname && url.search === window.location.search) return false;

    return true;
  }

  function prefetch(link) {
    if (!canPrefetch(link)) return;

    const url = new URL(link.href, window.location.href);
    const key = `${url.pathname}${url.search}`;
    if (prefetched.has(key)) return;
    prefetched.add(key);

    const node = document.createElement("link");
    node.rel = "prefetch";
    node.href = url.href;
    node.as = "document";
    document.head.appendChild(node);
  }

  function bind(link) {
    link.addEventListener("pointerdown", (event) => {
      if (event.pointerType === "mouse" && event.button !== 0) return;
      prefetch(link);
    }, { passive: true });

    link.addEventListener("touchstart", () => prefetch(link), { passive: true, once: true });
  }

  document.addEventListener("DOMContentLoaded", () => {
    if (!canUsePrefetch()) return;
    document.querySelectorAll("a[href]").forEach(bind);
  });
})();
