import assert from "node:assert/strict";
import { existsSync } from "node:fs";
import { join } from "node:path";
import { test } from "node:test";
import * as Effect from "effect/Effect";
import * as FileSystem from "effect/FileSystem";
import { Command, type CommandCall } from "../lib/command.ts";
import { latestStableTag, updateReferences } from "../lib/references.ts";
import { loadReference } from "../lib/upstream.ts";

test("stable library tags sort numerically and exclude prereleases", () => {
  assert.equal(latestStableTag(["tailwind-merge@3.9.0", "tailwind-merge@3.10.0", "v4.0.0-beta.1", "random"]), "tailwind-merge@3.10.0");
  assert.equal(latestStableTag(["v1.0.0-beta"]), undefined);
});

test("version inspection never fetches, installs or changes references", async (t) => {
  t.mock.method(console, "log", () => {});
  const calls: CommandCall[] = [];
  const fs = FileSystem.makeNoop({ exists: () => Effect.succeed(true) });
  await Effect.runPromise(updateReferences("libs", { check: true, yes: false, target: "" }).pipe(
    Effect.provideService(FileSystem.FileSystem, fs),
    Effect.provideService(Command, { run: (call) => {
      calls.push(call);
      return Effect.succeed({ code: 0, stdout: call.args?.[0] === "describe" ? "v1.0.0\n" : "abc123\n", stderr: "" });
    } }),
  ));
  assert.equal(calls.length, 6);
  assert.ok(calls.every((call) => call.command === "git" && ["rev-parse", "describe"].includes(call.args![0]!)));
});

test("typography updates extract the checked-out version before running plugin tests", async (t) => {
  t.mock.method(console, "log", () => {});
  const calls: CommandCall[] = [];
  const fs = FileSystem.makeNoop({ exists: (path) => Effect.succeed(path.endsWith(join("reference", "tailwindcss-typography")) || existsSync(path)) });
  await Effect.runPromise(updateReferences("plugins", { check: false, yes: true, target: "typography" }).pipe(
    Effect.provideService(FileSystem.FileSystem, fs),
    Effect.provideService(Command, { run: (call) => {
      calls.push(call);
      const stdout = call.args?.[0] === "tag" ? "v0.5.19\nv0.5.20\n" : call.args?.[0] === "describe" ? "v0.5.19\n" : "abc123\n";
      return Effect.succeed({ code: 0, stdout, stderr: "" });
    } }),
  ));
  const checkout = calls.findIndex((call) => call.command === "git" && call.args?.[0] === "checkout" && call.args[1] === "v0.5.20");
  const extraction = calls.findIndex((call) => call.command === "php" && call.args?.[0] === "test-coverage/plugins/extract-typography-tests.php");
  const tests = calls.findIndex((call) => call.command === "composer" && call.args?.[0] === "test-plugins");
  assert.ok(checkout >= 0, "the new typography version must be checked out");
  assert.ok(extraction > checkout, "the existing extractor must regenerate fixtures after checkout");
  assert.ok(tests > extraction, "plugin tests must run against regenerated fixtures");
});

test("upstream source bundling preserves const enums and function names", async () => {
  const layer = (await import("../lib/runtime.ts")).runtimeLayer;
  const result = await Effect.runPromise(Effect.scoped(loadReference<{ named: () => number }>("const enum Value { Seven = 7 }; export function named() { return Value.Seven; }")).pipe(Effect.provide(layer)));
  assert.equal(result.named(), 7);
  assert.equal(result.named.name, "named");
});
