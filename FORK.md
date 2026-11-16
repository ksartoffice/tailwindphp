# このフォークについて

[inline0/tailwindphp](https://github.com/inline0/tailwindphp) を PHP 7.4 以上で使うためのフォーク。
公開 API と本家のテスト期待値を維持し、Windows と旧クラス名の互換性を追加する。

| 項目 | 値 |
| --- | --- |
| 準備するフォーク版 | `v1.8.4.1`（タグ公開は別作業） |
| 取り込んだ本家 | `v1.8.4` / `590c3f183b05cdec20cf86f107b103238ce0d92b` |
| Tailwind CSS | `4.3.3` |
| PHP 要件 | 本家 `^8.2` → フォーク `^7.4 || ^8.0` |
| 必須拡張 | `ctype`、`mbstring` |

Composer 向けの版は Git タグで識別し、composer.json に version は追加しない。
CLI の表示と CHANGELOG.md は本家の `1.8.4` を維持する。フォーク固有の変更は本書へ記録する。

## 維持する差分

### PHP 7.4

- `match`、union 型、`mixed` 型、第一級 callable、名前付き引数、関数宣言の末尾カンマなど、PHP 8 専用の構文を等価な処理へ変換する。
- `src/_tailwindphp/polyfills.php` の文字列関数 polyfill を、Composer の files autoload と単独 autoload.php の両方で先頭に読み込む。両者のファイル順序は同じにする。
- PHPUnit 9 を維持し、Test / DataProvider 属性は PHPDoc に変換する。JSON の flags 引数などは位置引数へ変換し、省略した既定値も維持する。期待値や比較用データは変更しない。
- PHPStan は既存の PHP 7.4 設定を維持し、整形は PHP-CS-Fixer を使う。開発スクリプトの PHP 実行には Composer の `@php` を使う。

### Windows の CSS import

`src/index.php` の resolveImportUri() は、Unix の絶対パスに加え `C:\path\file.css` と `C:/path/file.css` を解決する。
本家 v1.8.4 にこの分岐はない。ImportPathsTest を Windows の PHP 7.4 でも実行する。
単独 autoload の子プロセステストは、PHP コードの引用符が cmd.exe に削除されないよう proc_open の配列形式で起動する。

### 旧クラスのコンパイル

本家 v1.8.2 以降の `src/compat/legacy-utilities.php` を採用する。
`bg-gradient-to-*`、`flex-grow` / `flex-shrink`、`start-*` / `end-*`、`decoration-*`、`overflow-ellipsis`、`max-w-screen-*` のフォーク独自登録は除去し、二重登録しない。
本家の旧クラス比較データで、無効な modifier、負数、任意値、カスタムテーマを含む出力を検証する。

### tailwind-merge の旧クラス互換

本家の tailwind-merge 3.7.0 用 default-config.php と生成用データはそのまま維持する。
`src/_tailwindphp/lib/tailwind-merge/tw-merge.php` の設定変換コールバックで、次の旧名を既存グループへ追加する。

- `bg-gradient-to-*` → bg-image
- `flex-grow` / `flex-shrink` → grow / shrink
- `decoration-slice` / `decoration-clone` → box-decoration
- `overflow-ellipsis` → text-overflow

生成ファイルの再生成で独自差分が消えない構成とし、旧名と新名の両順序・variant・important の優先順位を確認する。

## 開発・検証

Composer 利用者に Node は不要。TypeScript の開発ツールは package.json と pnpm-lock.yaml に固定した Node / pnpm を使う。
生成スクリプトの整形処理も PHP-CS-Fixer を呼び出す。本家の Tailwind CSS や比較用ライブラリの参照を今回の版より先へ更新しない。

```sh
composer install
composer test
composer quality
composer validate --strict
pnpm install --frozen-lockfile
pnpm typecheck
pnpm test:scripts
pnpm docs:check
```

PHPUnit は PHP 7.4 / 8.2 / 8.3 / 8.4 で実行する。Windows の PHP 7.4 では import、単独 autoload、公開 API、旧クラス互換を重点検証する。
PHP 7.4 で全 PHP ファイルを構文検査し、一時的な Composer 利用プロジェクトでも compile() と build() を確認する。
`.gitattributes` の LF 指定を維持する。生成 CSS と比較するテストのため、CRLF に一括変換しない。

## 本家を次回取り込むとき

作業ブランチで対象タグをマージし、本家が修正した差分は本家版へ統一する。
上記 PHP 7.4・Windows・tailwind-merge の差分を確認し、テストの期待値を緩めずソースを修正する。
main への反映は PR 経由で行う。フォークのタグ公開と Kayzart 側の依存更新は別々に実施する。
