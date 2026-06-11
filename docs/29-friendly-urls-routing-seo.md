# Friendly URLs, Routing & SEO

URL and routing configuration for a XenForo board: friendly (SEO) URLs and the
web server setup they need, route filters for rewriting route prefixes, SEO tools
(XML sitemap, IndexNow), the index page route, and the image/link proxy. This is
the admin-facing companion to `docs/04-controllers-routing.md` — that doc covers
how an add-on *registers* routes in code; this doc covers how the board owner
shapes the URLs those routes produce.

> **Sources** (official XenForo manual, verified verbatim mirror of docs.xenforo.com):
> - https://docs.xenforo.com/manual/configuration/friendly-urls
> - https://docs.xenforo.com/manual/configuration/route-filters
> - https://docs.xenforo.com/manual/configuration/seo
> - https://docs.xenforo.com/manual/configuration/sitemap
> - https://docs.xenforo.com/manual/configuration/index-page
> - https://docs.xenforo.com/manual/configuration/proxy

> **Scope note:** Everything below is drawn directly from the manual pages above.
> Where the manual does not document a detail (for example, web server platforms
> other than the five listed), it is intentionally omitted rather than guessed.

---

## Friendly URLs

Friendly URLs (also called SEO URLs) are web page addresses that are more readable
and convenient for humans. Without friendly URLs enabled, a thread may have a URL
like this:

```text
http://www.example.com/index.php?threads/thread-title-here.12345/
```

With friendly URLs enabled, that URL becomes:

```text
http://www.example.com/threads/thread-title-here.12345/
```

By default, XenForo does **not** enable friendly URLs, because of the web server
configuration requirements described below.

### Enabling friendly URLs

To enable friendly URLs, log in to your admin control panel and go to **Options**
and then **Search engine optimization (SEO)**. There are several options you may
wish to configure:

- **Use full friendly URLs** — Enabling this changes the structure of the URLs as
  shown above. The requirements for this vary based on your web server and are
  discussed in the next section.
- **Include content title in URLs** — Disabling this option makes your URLs much
  shorter, but less friendly to humans because no keywords are included in them.
  For example, with this option disabled, the thread URL above would become:

```text
http://www.example.com/threads/12345/
```

> **No lock-out risk:** If you enable friendly URLs but your web server can't
> support them, your admin control panel will still be accessible. You can go back
> in and turn the option off.

---

## Friendly URL web server requirements

Enabling the **Use full friendly URLs** option requires some web server
configuration or additional files. Find your web server software below.

### Apache

Apache is the most common web server available. If you are unsure what web server
you are running, it is likely Apache. XenForo includes the necessary configuration
file in the root directory.

If, after uploading XenForo, you do not see an `.htaccess` file in your XenForo
root directory, rename `htaccess.txt` to `.htaccess` (be sure to include the `.`
prefix). You should now be able to enable friendly URLs.

If, after enabling friendly URLs, your XenForo installation does not function
correctly, contact your host to confirm that they have **mod_rewrite** installed
and that they allow overrides via an `.htaccess` file.

### LiteSpeed Web Server

LiteSpeed Web Server reads and uses Apache configurations (including `.htaccess`
files) and will work using the Apache documentation above.

### IIS 7

To enable friendly URLs in IIS 7, put the following code into a `web.config` file
in your XenForo root directory:

```text
<?xml version="1.0" encoding="UTF-8"?>
<configuration>
    <system.webServer>
        <rewrite>
            <rules>
                <rule name="Imported Rule 1" stopProcessing="true">
                    <match url="^.*$" />
                    <conditions logicalGrouping="MatchAny">
                        <add input="{REQUEST_FILENAME}" matchType="IsFile" ignoreCase="false" />
                        <add input="{REQUEST_FILENAME}" matchType="IsDirectory" ignoreCase="false" />
                    </conditions>
                    <action type="None" />
                </rule>
                <rule name="Imported Rule 2" stopProcessing="true">
                    <match url="^(data|js|styles|install)" />
                    <action type="None" />
                </rule>
                <rule name="Imported Rule 3" stopProcessing="true">
                    <match url="^.*$" />
                    <action type="Rewrite" url="index.php" />
                </rule>
            </rules>
        </rewrite>
        <httpErrors existingResponse="PassThrough" />
    </system.webServer>
</configuration>
```

### Nginx

To enable friendly URLs in Nginx, you must put the following in your server
configuration:

```nginx
location /xf/ {
	try_files $uri $uri/ /xf/index.php?$uri&$args;
	index index.php index.html;
}

location ^~ /xf/install/data/ {
	internal;
}
location ^~ /xf/install/templates/ {
	internal;
}
location ^~ /xf/internal_data/ {
	internal;
}
location ^~ /xf/library/ { #legacy
    internal;
}
location ^~ /xf/src/ {
    internal;
}

location ~ \.php$ {
	try_files $uri =404;
	fastcgi_pass    127.0.0.1:9000;
	fastcgi_param   SCRIPT_FILENAME $document_root$fastcgi_script_name;
	include         fastcgi_params;
}
```

The `/xf/` paths must be changed to match your XenForo installation path.

> This configuration also helps protect web-based access to directories that
> aren't normally accessible (such as `internal_data` and `src`).

### Lighttpd

To enable friendly URLs in Lighttpd, ensure that you have the `mod_rewrite` module
loaded and add the following to your server configuration:

```text
url.rewrite = (
	"^/(data|install|js|styles)/(.*)$" => "$0",
	"^/(.*\.php)(.*)$" => "$0",
	"^/.*(\?.*)" => "/index.php$1",
	"" => "/index.php"
)
```

---

## Route filters

Route filters allow you to change the URLs generated by XenForo to more closely
fit your needs.

> **Warning:** Route filters are an advanced feature. They can create ambiguous
> URL structures that may prevent access to other pages. Test your changes
> carefully after setting up a route filter.

The route filter manager and editor is found under the **Setup** section of the
admin control panel.

### What is a route?

A **route** is XenForo's term for the part of the URL that XenForo uses to
determine which page to load. The **Use Full Friendly URLs** option affects the
URL but not the actual route. However, other options may affect the actual route
generated.

For example, if you have installed XenForo into `http://example.com/community/`,
the **route** portion of each URL is the part after `index.php?` (or after the
install path, with friendly URLs on):

- `http://example.com/community/index.php?` **`threads/example.1`** `/`
- `http://example.com/community/` **`threads/example.1`** `/`
- `http://example.com/community/index.php?` **`threads/example.1/page-2`**
- `http://example.com/community/` **`threads/example.1/page-2`**
- `http://example.com/community/index.php?` **`threads/example.1`** `/&example=query`
- `http://example.com/community/` **`threads/example.1`** `/?example=query`

The examples that show `index.php?` are the URL *without* friendly URLs enabled.
You can use this guide to determine the route for any URL.

> **Relevance to add-ons:** The route prefix here (`threads`, `forums`, etc.) is
> the same route prefix an add-on declares when it registers a route in code —
> see `docs/04-controllers-routing.md`. A route filter rewrites that prefix for
> end users without changing how the route is processed internally.

### Defining a route filter

A standard route filter is **bi-directional**. It changes the generated URLs to
your specified version and then, when a user loads that URL, converts it back to
the standard XenForo version so it can be processed. As such:

- **Find Route** should be the standard XenForo route.
- **Replace With** should be what you want the route to look like.

Both the find and replace fields can use wild cards. Wild cards take one of the
following forms:

| Wild card | Matches |
|---|---|
| `{name}` | Anything other than a `/` |
| `{name:digit}` | Only digits |
| `{name:string}` | Anything but a `/` or `.` |

Rules for wild cards and matching:

- The same wild cards must appear in **both** the find and replace fields.
- Route filters always match from the very beginning of a route. A filter does not
  have to match the whole route — it matches as much as it can and leaves the rest
  as is. This lets you change exactly what you want without affecting more specific
  parts of the URL.
- To prevent errors, a route filter must always begin with a **route prefix** that
  does **not** contain a wild card. In other words, you can't specify a wild card
  before the first `/` in the find or replace fields.

### Uni-directional (incoming-only) filters

If you want a uni-directional filter, select the **Incoming URL conversion only**
option. This effectively redirects an old route to a new route without changing
the value of the new route. When this is selected, any user visiting a route that
matches the **replace** value is redirected to the **find** value automatically.

### Example 1: Changing a route prefix

The default URLs in XenForo are in English. You may wish to change these to your
preferred language. For example, in Spanish, "forums" is "foros". To set up a
route filter to do this you would enter:

- **Find route:** `forums/`
- **Replace with:** `foros/`

This automatically matches all URLs within the `forums` route prefix, such as
`forums/example.1/`, which becomes `foros/example.1/`.

As another example, you may have the *Resource Manager* add-on installed and
prefer to call it *Downloads*. You could simply change `resources/` to
`downloads/`.

### Example 2: Changing a page node route

Page nodes in XenForo always have a URL structure of `pages/page-name/`, but maybe
you have a hierarchy of pages like:

- Parent (`pages/parent/`)
  - Child (`pages/child/`)
    - Grandchild (`pages/grandchild`)

With route filters, you can give your URLs a hierarchy as well:

- Find: `pages/parent/`, replace: `parent/`
- Find: `pages/child/`, replace: `parent/child/`
- Find: `pages/grandchild/`, replace: `parent/child/grandchild/`

This example also shows how you can remove the `pages/` prefix from specific pages.

> **Warning:** Be very careful that your page name does not interfere with any
> standard URLs. If a filter interferes with standard URLs, it will break other
> places in XenForo.

---

## The index page route

Assume you've installed XenForo into `http://example.com/community/`. When you
access this URL or `http://example.com/community/index.php`, a specific page in the
XenForo system must be loaded.

By default, this is the list of forums, or an overview of new posts (controlled by
the **Forums default page** option). However, you can change this to a page of
your choosing — a portal you've installed, the resource manager, or even a custom
page node.

This is controlled by the **Index page route** option in the **Basic board
information** group. It defaults to `forums/`. When you change this value, whatever
used to be at the index is now accessible by its default URL, and any links that
point to the new index route simply point to the
`http://example.com/community/` (or `.../index.php`) URL instead.

To change it, first identify the route of the page you want to set as the index
(see the [Route filters](#route-filters) section above for how to read a route).
Examples:

| Target page | Index page route |
|---|---|
| Forum list | `forums/` |
| A page node | `pages/page-name/` (change `page-name` as necessary) |
| The recent activity list | `recent-activity/` |
| The resource manager | `resources/` (only applies with the necessary add-on) |
| A custom portal | `portal/` (may differ depending on the portal add-on) |

After changing the index route, check that the index URL displays the content you
expect.

---

## Search engine management (SEO)

Most sites want their pages indexed by search engines such as Google and Bing, so
that web searches for relevant queries return content from the forums. While search
engines are adept at *spidering* a forum, XenForo offers a selection of tools to
help them read and index content as efficiently as possible.

### XML sitemap generation and submission

One such method is building and submitting an XML sitemap: essentially a list of
all the pages from your site that you want search engines to include. XenForo can
do this for you.

Options controlling how the sitemap is built, which content types are included,
and whether or not the sitemap is automatically submitted to Google are available
at **Setup > Options > XML sitemap generation**.

A further **Extra sitemap URLs** option lets you list additional URLs you want
included in the automatically-generated sitemap.

> The dedicated *Sitemap* manual page simply points to this same XML sitemap
> generation section — the sitemap is configured through the SEO options described
> here.

### IndexNow

Going a step beyond building an XML sitemap, [IndexNow](https://www.indexnow.org)
provides a way for your site to instantly and directly inform search engines about
your latest content changes. The IndexNow system lets search engines understand
which pages have changed so they can prioritize refreshing those pages in their
index.

Support for IndexNow is as simple as enabling the
**Setup > Options > Search engine optimization (SEO) > IndexNow** option.

Further information about IndexNow is available at
[IndexNow.org](https://www.indexnow.org).

---

## Image and link proxy

It may be advantageous for your site to act as a proxy for any hot-linked images
and links posted in user messages.

Proxying images can have several benefits, including:

- Assurance that the image will remain available to your visitors even if the
  original image is removed from its source site.
- The ability to track metrics of how many times images have been viewed by your
  visitors.

> **Bandwidth note:** Acting as an image proxy will increase the amount of
> bandwidth used by your site, because your own server is responsible for fetching
> the original image and then serving it to any visitor who requests it.

To enable the image and/or link proxying service, visit the **Image and link
proxy** section of the options system. There you can set parameters for your
proxy, including:

- How often your server will check for updates of the original source image.
- How large images can be before your site opts to keep them hot-linked instead of
  proxying them.

---

## Checklist

- [ ] Decide whether to enable **Use full friendly URLs** (Options > SEO).
- [ ] Apply the correct web server config for your platform (Apache `.htaccess`,
      IIS 7 `web.config`, Nginx/Lighttpd server config) before enabling.
- [ ] Confirm the board still works after enabling; if not, turn the option off
      (the admin CP stays accessible) and check `mod_rewrite`/override support.
- [ ] Keep route filters anchored on a non-wildcard route prefix, with matching
      wild cards in find and replace; test them carefully.
- [ ] Verify the **Index page route** displays the page you expect.
- [ ] Configure XML sitemap content types and (optionally) IndexNow under
      Setup > Options.
- [ ] Configure image/link proxy thresholds if proxying is enabled.

**See also:** `docs/04-controllers-routing.md` (how add-ons register routes and
route prefixes in code — the internal counterpart to the route filters and index
route options described here).
