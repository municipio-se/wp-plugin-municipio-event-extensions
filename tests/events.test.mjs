import { test } from "node:test";
import assert from "node:assert/strict";
import { readFile } from "node:fs/promises";
const source = await readFile(
  new URL("../assets/events.js", import.meta.url),
  "utf8",
);
const { install, moduleUrl } = await import(
  `data:text/javascript;base64,${Buffer.from(source).toString("base64")}`
);

test("submission preserves live state of other modules and resets own page", () => {
  const url = moduleUrl(
    "https://example.test/?event_page_1=4&event_search_2=new&keep=yes",
    "https://example.test/#event-module-1",
    [
      ["event_search_1", "music"],
      ["event_search_2", "stale"],
      ["event_categories_1[]", "8"],
      ["event_categories_1[]", "9"],
    ],
    "1",
  );
  assert.equal(url.searchParams.get("event_search_2"), "new");
  assert.equal(url.searchParams.has("event_page_1"), false);
  assert.deepEqual(url.searchParams.getAll("event_categories_1[]"), ["8", "9"]);
  assert.equal(url.searchParams.get("keep"), "yes");
});

function setup(fetcher) {
  const handlers = {};
  const calls = [];
  const status = { setAttribute() {}, textContent: "" };
  const result = { focus: () => calls.push("focus") };
  const replacement = {
    matches: () => true,
    dataset: { resultMessage: "Updated" },
    querySelector: () => result,
  };
  const attrs = new Map();
  const module = {
    id: "event-module-1",
    dataset: { eventModule: "1", loading: "Loading" },
    setAttribute: (k, v) => attrs.set(k, v),
    removeAttribute: (k) => attrs.delete(k),
    replaceWith: () => calls.push("replace"),
  };
  const form = {
    closest: () => module,
    method: "get",
    action: "https://example.test/#event-module-1",
  };
  const win = {
    document: {
      createElement: () => status,
      body: { append() {} },
      addEventListener: (name, handler) => {
        handlers[name] = handler;
      },
    },
    location: {
      href: "https://example.test/",
      origin: "https://example.test",
      pathname: "/",
      assign: () => calls.push("navigate"),
      reload: () => calls.push("reload"),
    },
    history: {
      state: { other: true },
      pushState: (state) => {
        assert.deepEqual(state, { other: true });
        calls.push("history");
      },
    },
    fetch: fetcher,
    DOMParser: class {
      parseFromString() {
        return { getElementById: () => replacement };
      }
    },
    FormData: class {
      *[Symbol.iterator]() {
        yield ["event_search_1", "test"];
      }
    },
    setTimeout: () => 1,
    clearTimeout() {},
    addEventListener: (name, handler) => {
      handlers[name] = handler;
    },
  };
  install(win);
  const submit = () =>
    handlers.submit({
      target: form,
      preventDefault: () => calls.push("prevent"),
    });
  return { win, submit, calls, status, replacement, handlers, attrs };
}
const flush = () => new Promise((resolve) => setImmediate(resolve));
const response = () => ({
  ok: true,
  redirected: false,
  text: async () => "fixture",
});

test("successful update replaces module, preserves history state and focuses results", async () => {
  const fixture = setup(async () => response());
  fixture.submit();
  await flush();
  assert.deepEqual(fixture.calls, ["prevent", "history", "replace", "focus"]);
  assert.equal(fixture.status.textContent, "Updated");
  assert.equal(fixture.attrs.has("aria-busy"), false);
});

test("HTTP failure or missing module navigates without deleting existing results", async () => {
  for (const missing of [false, true]) {
    const fixture = setup(async () => (missing ? response() : { ok: false }));
    if (missing) fixture.replacement.matches = () => false;
    fixture.submit();
    await flush();
    assert.deepEqual(fixture.calls, ["prevent", "navigate"]);
  }
});

test("a superseded response cannot replace newer results", async () => {
  const resolvers = [];
  const fixture = setup(
    () => new Promise((resolve) => resolvers.push(resolve)),
  );
  fixture.submit();
  fixture.submit();
  resolvers[1](response());
  await flush();
  resolvers[0](response());
  await flush();
  assert.equal(fixture.calls.filter((call) => call === "replace").length, 1);
  assert.equal(fixture.calls.includes("navigate"), false);
});

test("history navigation cancels pending updates and reloads complete page state", async () => {
  let resolve;
  const fixture = setup(
    () =>
      new Promise((done) => {
        resolve = done;
      }),
  );
  fixture.submit();
  fixture.handlers.popstate();
  resolve(response());
  await flush();
  assert.deepEqual(fixture.calls, ["prevent", "reload"]);
});

test("legacy global filter URLs retain ordinary form navigation", () => {
  const fixture = setup(() => {
    throw new Error("Should not fetch");
  });
  fixture.win.location.href += "?categories=1&categories=2";
  fixture.submit();
  assert.deepEqual(fixture.calls, []);
});
