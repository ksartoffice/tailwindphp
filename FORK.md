# このフォークについて

[dnnsjsk/tailwindphp](https://github.com/dnnsjsk/tailwindphp) のフォーク。
本家に対して **PHP 7.4 対応** と **TailwindCSS v3 互換ユーティリティ** を追加している。

本家の変更は定期的に取り込む。このファイルは「取り込みのたびに再適用が必要な差分」を
記録したもので、作業手順は末尾の [upstream の取り込み手順](#upstream-の取り込み手順) を参照。

| 項目 | 本家 | このフォーク |
| --- | --- | --- |
| 必要 PHP | `^8.2` | `^7.4 \|\| ^8.0` |
| 追従中の本家バージョン | — | **v1.7.0** (TailwindCSS 4.3.3) |

## 1. PHP 7.4 対応

本家は PHP 8.2 以上を前提にしているため、取り込みのたびに以下を再適用する必要がある。
本家は PHP 8 専用構文をそれほど多用しておらず、v1.3.2 → v1.7.0 の 5,665 行の新規コードで
再適用が必要だったのは実質 15 箇所程度だった。

### 1.1 除去が必要な構文

| 構文 | PHP | 対応 |
| --- | --- | --- |
| `match (…) { … }` | 8.0+ | `switch` に書き換え |
| union 型 (`string\|array $x`) | 8.0+ | 型宣言を削除（PHPDoc に残す） |
| `mixed` 型 | 8.0+ | 型宣言を削除（PHPDoc に残す） |
| 関数 **宣言** の引数リスト末尾カンマ | 8.0+ | 削除（関数 *呼び出し* の末尾カンマは 7.3+ で合法なので触らない） |
| 名前付き引数 `f(recursive: true)` | 8.0+ | 位置引数に変更 |
| `?->` / `enum` / `readonly` / コンストラクタプロモーション | 8.0+ | 現時点で本家は未使用 |

### 1.2 polyfill

[src/_tailwindphp/polyfills.php](src/_tailwindphp/polyfills.php) で `str_contains()` /
`str_starts_with()` / `str_ends_with()` を補う。composer.json の `autoload.files` の
**先頭** に登録すること（他のファイルが読み込み時に使うため）。

### 1.3 テストコード

本家は PHPUnit 10 の属性を使うが、PHP 7.4 では PHPUnit 9 系しか使えないため
アノテーションに変換する。

```
#[Test]                      →  /** @test */
#[DataProvider('provider')]  →  /** @dataProvider provider */
```

既存の docblock がある場合はその中にタグを追記する。変換スクリプトの例は
このファイルの [変換スクリプト](#変換スクリプト) を参照。

### 1.4 その他

- `phpstan.neon` に `phpVersion: 70400` を指定している。この指定により本家では出ない
  型推論の偽陽性が出ることがあるため、`ignoreErrors` に数件の追加がある。
- `composer.json` の `scripts` は `@php` プレフィックスを使う（本家は素の `php`）。

## 2. 独自の修正・拡張

### 2.1 Windows 絶対パスの `@import` 解決

`resolveImportUri()`（[src/index.php](src/index.php)）に `C:\path\to\file.css` 形式を
解決する分岐を追加している。本家は Unix 形式の絶対パス（`/` 始まり）しか見ていないため、
Windows では `importPaths` を使った `@import` が解決できない。

**本家に同等の修正は入っていない。取り込みのたびに消えていないか確認すること。**
消えると `tests/ImportPathsTest.php` の `test_import_with_layer_modifier` と
`test_file_import_deduplication` が落ちる。

### 2.2 TailwindCSS v3 互換ユーティリティ

v4 で廃止された v3 のクラス名を使えるようにするための独自拡張。
**不具合修正ではないので本家には入らない。** 恒久的に自前で維持する。

| クラス | 対応先 | 定義場所 |
| --- | --- | --- |
| `bg-gradient-to-{t,tr,r,br,b,bl,l,tl}` | `bg-linear-*` | [src/utilities/backgrounds.php](src/utilities/backgrounds.php) |
| `flex-grow` / `flex-shrink` | `grow` / `shrink` | [src/utilities/flexbox.php](src/utilities/flexbox.php) |
| `start-*` / `end-*` | `inset-s-*` / `inset-e-*` | [src/utilities/layout.php](src/utilities/layout.php) |
| `decoration-clone` / `decoration-slice` | `box-decoration-*` | [src/utilities/layout.php](src/utilities/layout.php) |
| `overflow-ellipsis` | `text-ellipsis` | [src/utilities/layout.php](src/utilities/layout.php) |
| `max-w-screen-{breakpoint}` | `--breakpoint` テーマ参照 | [src/utilities/sizing.php](src/utilities/sizing.php) |

tailwind-merge 側にも対応するエイリアス定義がある
（[src/_tailwindphp/lib/tailwind-merge/default-config.php](src/_tailwindphp/lib/tailwind-merge/default-config.php)）。
ユーティリティを足すときは両方を更新すること。

テストは [tests/tailwindphp/UtilitiesTest.php](tests/tailwindphp/UtilitiesTest.php) と
[tests/PublicApiTest.php](tests/PublicApiTest.php) にある。

### 2.3 本家に取り込まれて不要になったもの

- **`@import "tailwindcss"` のレイヤー構造** — 独自に修正していたが、本家が v1.4.2 で
  同等の修正を入れたため、v1.7.0 取り込み時に本家版を採用して破棄した。

## 3. 改行コード

`.gitattributes` で `* text=auto eol=lf` を指定している。

Git for Windows はシステム設定で `core.autocrlf=true` になっていることが多く、
放置するとチェックアウト時に CRLF に変換される。一部のテストは生成 CSS（常に LF）を
PHP の heredoc リテラルと比較するため、**CRLF だとテストが落ちる**
（例: `tests/ContainerUtilityTest.php`）。

チェックアウト済みのファイルが CRLF になってしまった場合:

```bash
git add --renormalize .
rm -f .git/index && git reset --hard HEAD
```

## upstream の取り込み手順

```bash
# 1. 本家の変更を取得
git fetch upstream --tags

# 2. 作業ブランチを作ってマージ
git checkout -b upgrade/vX.Y.Z main
git merge vX.Y.Z
```

### 3. 競合の解決方針

- **本家が全面書き換えしたテストファイル** — 本家版をそのまま採用し（`git checkout --theirs`）、
  後段の属性変換をかけ直す。手でマージしようとしないこと。
- **本家が同等の修正を入れた自前パッチ** — 本家版を採用する。
- **`composer.json` / `phpstan.neon`** — 両者を統合する。PHP 7.4 制約、polyfill の
  `autoload.files` 登録、`phpVersion: 70400`、`@php` プレフィックスを必ず残す。

### 4. PHP 7.4 化の再適用

```bash
# PHP 8 専用の型構文・構文が残っていないか検査（トークン単位）
# 正規表現での grep はビット演算子 | や preg_match() を誤検出するので使わない
for f in $(git ls-files '*.php'); do
  php -r '$t=token_get_all(file_get_contents($argv[1]));
    foreach($t as $k){ if(is_array($k) && in_array(token_name($k[0]),
      ["T_MATCH","T_ENUM","T_READONLY","T_NULLSAFE_OBJECT_OPERATOR"]))
      echo $argv[1].": ".token_name($k[0])."\n"; }' "$f"
done
```

union 型・`mixed` 型・宣言の末尾カンマも同様にトークン単位で検査する。

### 変換スクリプト

PHPUnit 属性の変換は機械的に行える。

```python
import re, sys, pathlib

ATTR = re.compile(r'^(?P<indent>[ \t]*)#\[(?P<name>Test|DataProvider)(?:\((?P<arg>[^)]*)\))?\]\s*$')

def tag_for(name, arg):
    if name == 'Test':
        return '@test'
    m = re.search(r"['\"]([^'\"]+)['\"]", arg or '')
    return '@dataProvider ' + (m.group(1) if m else '')

def convert(text):
    out = []
    for line in text.split('\n'):
        m = ATTR.match(line)
        if not m:
            out.append(line)
            continue
        indent, tag = m.group('indent'), tag_for(m.group('name'), m.group('arg'))
        j = len(out) - 1
        while j >= 0 and out[j].strip() == '':
            j -= 1
        if j >= 0 and out[j].strip() == '*/':
            out.insert(j, indent + ' * ' + tag)   # 既存 docblock に追記
        else:
            out += [indent + '/**', indent + ' * ' + tag, indent + ' */']
    return '\n'.join(out)

for path in sys.argv[1:]:
    p = pathlib.Path(path)
    p.write_text(convert(p.read_text(encoding='utf-8')), encoding='utf-8', newline='')
```

### 5. 検証

```bash
composer dump-autoload
php vendor/bin/phpunit          # 全パスすること
php vendor/bin/phpstan analyse --memory-limit=512M
php vendor/bin/php-cs-fixer fix --dry-run --diff
```

独自拡張が生きているかは実際に CSS を生成して確認する。

```bash
php -r 'require "vendor/autoload.php";
$c = ["bg-gradient-to-r","flex-grow","flex-shrink","decoration-clone",
      "overflow-ellipsis","max-w-screen-md","start-0","end-4"];
$css = TailwindPHP\Tailwind::generate([
  "content" => "<div class=\"".implode(" ", $c)."\"></div>",
  "css" => "@import \"tailwindcss\";"]);
foreach ($c as $x) printf("%-20s %s\n", $x,
  strpos($css, "." . str_replace("/", "\\/", $x)) !== false ? "OK" : "MISSING");'
```

### 6. 注意

`CliTest::application_version_matches_changelog` は `CHANGELOG.md` の先頭にある
`## [X.Y.Z]` 見出しと `Cli\Application::VERSION` の一致を検証する。
CHANGELOG は本家のものをそのまま維持し、フォーク独自のエントリは追加しない
（追加すると 3 桁バージョンの制約に引っかかる）。フォーク側の変更はこのファイルに記録する。
