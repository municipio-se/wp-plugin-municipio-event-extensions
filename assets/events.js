const legacy = [
  "currentPage",
  "searchString",
  "startDate",
  "endDate",
  "categories",
  "tags",
  "minAge",
  "maxAge",
];

function owns(name, id) {
  return (
    name.startsWith("event_") && new RegExp(`_${id}(?:\\[.*\\])?$`).test(name)
  );
}

// Other modules' hidden controls may predate their latest update. Keep their
// current URL state instead of re-submitting those stale controls.
export function moduleUrl(current, target, entries, id) {
  const url = new URL(current);
  url.hash = new URL(target, current).hash;
  for (const name of [...url.searchParams.keys()]) {
    if (owns(name, id) || legacy.includes(name)) url.searchParams.delete(name);
  }
  for (const [name, value] of entries) {
    if (owns(name, id)) url.searchParams.append(name, value);
  }
  return url;
}

export function install(win) {
  const doc = win.document;
  let pending;
  const status = doc.createElement("p");
  status.className = "u-sr__only";
  status.setAttribute("role", "status");
  status.setAttribute("aria-live", "polite");
  doc.body.append(status);

  async function update(module, url) {
    pending?.controller.abort();
    const controller = new AbortController();
    const operation = { controller, module };
    pending = operation;
    module.setAttribute("aria-busy", "true");
    status.textContent = module.dataset.loading;
    // A hung enhancement must not prevent the ordinary navigation indefinitely.
    const timeout = win.setTimeout(() => controller.abort(), 15000);
    try {
      const response = await win.fetch(url, {
        signal: controller.signal,
        credentials: "same-origin",
      });
      if (!response.ok || response.redirected)
        throw new Error("Unexpected event response");
      const html = await response.text();
      const page = new win.DOMParser().parseFromString(html, "text/html");
      const replacement = page.getElementById(module.id);
      if (!replacement?.matches("[data-event-module]"))
        throw new Error("Missing event module");
      if (pending !== operation) return;
      if (controller.signal.aborted) throw new Error("Event request timed out");
      // Only the server-rendered module is imported; page scripts are not run.
      win.history.pushState(win.history.state, "", url);
      module.replaceWith(replacement);
      replacement.querySelector("[data-event-results]")?.focus();
      status.textContent = replacement.dataset.resultMessage;
    } catch {
      if (pending === operation) win.location.assign(url.href);
    } finally {
      win.clearTimeout(timeout);
      if (pending === operation || pending?.module !== module)
        module.removeAttribute("aria-busy");
      if (pending === operation) pending = undefined;
    }
  }

  doc.addEventListener("submit", (event) => {
    const form = event.target;
    const module = form.closest("[data-event-module]");
    if (event.defaultPrevented || !module || form.method !== "get") return;
    if (legacy.some((key) => new URL(win.location.href).searchParams.has(key)))
      return;
    const url = moduleUrl(
      win.location.href,
      form.action,
      new win.FormData(form),
      module.dataset.eventModule,
    );
    event.preventDefault();
    void update(module, url);
  });

  doc.addEventListener("click", (event) => {
    if (
      event.defaultPrevented ||
      event.button !== 0 ||
      event.metaKey ||
      event.ctrlKey ||
      event.shiftKey ||
      event.altKey
    )
      return;
    const link = event.target.closest("a[data-event-navigation]");
    const module = link?.closest("[data-event-module]");
    if (
      !module ||
      link.hasAttribute("download") ||
      (link.target && link.target !== "_self")
    )
      return;
    const target = new URL(link.href);
    if (
      target.origin !== win.location.origin ||
      target.pathname !== win.location.pathname
    )
      return;
    // Legacy filters are global, so use normal navigation to refresh every
    // consumer instead of changing one module and leaving others out of sync.
    if (
      legacy.some(
        (key) =>
          target.searchParams.has(key) ||
          new URL(win.location.href).searchParams.has(key),
      )
    )
      return;
    const url = moduleUrl(
      win.location.href,
      target,
      target.searchParams,
      module.dataset.eventModule,
    );
    event.preventDefault();
    void update(module, url);
  });

  // A history entry describes the whole page, including other modules. Reload
  // it through the working server baseline rather than restoring partial state.
  win.addEventListener("popstate", () => {
    pending?.controller.abort();
    pending = undefined;
    win.location.reload();
  });
}

if (
  typeof window !== "undefined" &&
  window.fetch &&
  window.AbortController &&
  window.DOMParser
)
  install(window);
