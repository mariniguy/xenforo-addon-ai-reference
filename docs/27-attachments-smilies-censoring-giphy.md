# Attachments, Smilies, Word Censoring & Giphy

Four content-handling features XenForo administers from the **Content** section of
the admin control panel (plus the main **Options** system): file **attachments**,
**smilies**, **word censoring**, and **Giphy** GIF integration. This page reflects
the official manual for each; these are administrator-facing features, so the notes
below describe behavior and settings rather than developer APIs.

> **Sources** (official XenForo manual, verbatim):
> - https://docs.xenforo.com/manual/content/attachments
> - https://docs.xenforo.com/manual/content/smilies
> - https://docs.xenforo.com/manual/content/censoring
> - https://docs.xenforo.com/manual/content/giphy

---

## Attachments

Attachments are files uploaded along with users' posts etc. They are frequently
images, but can also be text files and other types.

> **Allowed file types:** You define exactly what kind of files can be uploaded as
> attachments using the **Allowed attachment file extensions** option in the main
> XenForo options system. Further settings for controlling how users may attach
> files are found in the **Attachments** section of the main options system.

### Attachment manager

The attachment manager (in the **Content** section of the admin control panel) lets
you review and manage attachments uploaded to your forum.

| Capability | Description |
|---|---|
| Sort | Switch between the **most recent** and the **largest** attachments. |
| Filter | Narrow results down to specific **file types**, **owner names**, and **dates**. |
| View | View an attachment right there in the manager. |
| View in context | View it in the context of its host content (the user post it was attached to). |
| Delete | Delete a single attachment. |
| Bulk delete | Select the checkbox next to each attachment, then use the **Delete** control at the bottom of the list to delete multiple at once. |

> **Deletion is permanent.** Once attachments have been deleted they cannot be
> recovered, and their host content may no longer make sense without the attached
> file.

---

## Smilies

Smilies (also known as *emoticons*) are small graphical items used to convey emotion
or meaning in textual content, where specific text combinations are automatically
converted into iconic imagery. The most common smilie is `:)`, which is replaced by
a smiling face icon.

XenForo ships with a collection of default smilies and can be extended to add any
number of additional smilies. The smilie manager is found in the **Content** section
of the admin control panel.

### Smilie manager

All smilies currently available are listed in the smilie manager, along with their
name and any text combinations that trigger them. For example, the **Smile** smilie
can be triggered with any one of `:)`, `:-)` or `(:`.

| Action | How |
|---|---|
| Add | Click the **Add smilie** button at the top of the page. |
| Edit | Click on a smilie's name. |
| Delete | Use the delete icon next to the smilie. |
| Import / Export | Use the **Import** / **Export** tools (Import is in the drop-down menu beside **Add smilie**). |

#### Categories

For ease of management, smilies can be attached to **smilie categories**, and the
manager will display all your smilies in their respective categories if you choose to
use this system. If you want to attach a smilie to a category but none are shown in
the list, go back and create new categories from the manager page.

#### Import and export

If you have built a large collection of smilies and want to share them, use the
**Export** tools in the smilie manager. Likewise, if you have acquired a collection
from another source, you can bring them in with the **Import** tool (available in the
drop-down menu in the bar of controls with **Add smilie**).

### Smilie editor

Within the smilie editor you make all necessary changes to your smilies. The most
important, non-obvious controls are described below.

#### Text to replace

Enter the text combination(s) you would like replaced by this smilie icon. To apply
multiple combinations to a single smilie, enter each one on a single line in the text
box.

> **Combinations must be unique.** If you try to apply the same combination to more
> than one smilie, the system alerts you that the text combination is already in use
> and prevents you from saving the smilie data.

#### Image replacement URL

Enter the path to your smilie icon graphic. An absolute URL can be useful here, but
if you use a relative URL it should be relative to the XenForo home directory (where
`index.php` lives).

#### 2x image replacement URL

For users on high-pixel-density devices (e.g. Apple's *Retina* screens), normal
resolution graphics may appear blocky. Use the **2x** box to define a path to a
higher-resolution version of the icon for these users.

> **Note:** As of XenForo 2.2, you can upload smilie graphics from your computer
> directly from the control panel by clicking the gadget attached to the URL text
> fields.

#### Smilie category, Display order and Show this smilie in text editor

These fields control how smilies are presented to users in the text editor and on the
smilie help page:

- **Display order** — smilies with higher [display order](18-navigation-display-order.md)
  values are shown after those with low display order values.
- **Smilie category** — if no categories appear in the list, create them first from
  the manager page.
- **Show this smilie in text editor** — controls visibility in the editor's smilie
  list.

> **"Show this smilie" only affects display.** Turning it off only prevents the
> smilie from appearing in the list shown when users click the smilie button on the
> text editor. It does **not** prevent the smilie from being used if a user enters
> its text replacement combination.

#### Sprite mode

In some cases (such as the default XenForo smilies), smilies come as part of a large
sheet of images rather than as separate, individual images. The **sprite mode**
controls let you define coordinates to pick out a single smilie from within the sheet
of images.

---

## Censoring user-generated content

Administrators commonly want to censor certain words or phrases so they do not appear
on their sites when posted by visiting users. XenForo has a comprehensive censoring
system, accessed through the **Censoring** section of the main options system at
**Setup > Options > Censoring**.

To censor a word or word fragment, enter your term in an empty **Words to censor**
box. You may use a `*` wildcard character to match any text.

### Wildcard matching

| Pattern | Matches |
|---|---|
| `dog` | `dog` only |
| `dog*` | `dog`, `dogs`, `dogmatic`, etc. |
| `d*g` | `dog`, `dug`, etc. |
| `d*g*` | `dog`, `dug`, `dogs`, `dogmatic`, `duggery`, etc. |

> **Not case-sensitive.** Censor words are not case-sensitive, so any combination of
> `DoG`, `dOG`, `doG` etc. will match `dog`.

### Replacement behavior

Each censor word is normally replaced by a repeating string of the **Censor
character**, which is an asterisk `*` by default. The replacement string matches the
length of the **matched** text:

- A three-letter censored word is replaced with three asterisks `***`.
- `dogmatic`, having been matched by `dog*`, is replaced with eight asterisks
  `********`.

### Special replacements

Alternatively, each censor word can have a replacement word. For example, you could
have `dog` replaced with `canine` by entering the replacement word into the
**Replacement** box next to `dog`.

---

## Enable Giphy support

Enabling [Giphy](https://giphy.com/) support lets your users search for GIFs while
composing messages on your forum. Enable it under **Options > Messages** by clicking
**Enable GIPHY support**. You will also need to obtain a Giphy API key and upgrade it
to a production key.

> **Beta key limits:** A newly created Giphy API beta key is rate limited to a maximum
> of **42 search requests an hour** and **1000 search requests a day**. To avoid these
> limits, upgrade the key to a production key.

### Obtaining a Giphy API production key

1. Go to the [GIPHY Developers](https://developers.giphy.com/docs/api/) page and click
   **Create an App**.
2. Log in if you already have a Giphy account; otherwise click **Join GIPHY!** to
   create one.
3. Once logged in to the [Developers Dashboard](https://developers.giphy.com/dashboard/),
   click **Create an App**.
4. In the overlay, choose between SDK or API — click **Select API**, then **Next Step**.
5. Enter a name in the **Your App Name** field, in the format:
   > [Your forum name] (GIPHY integration for XenForo)
6. In the **App Description** field, enter:
   > While writing a post using XenForo, users can click the GIF button to see the
   > featured GIFs via the GIPHY API. Users can search using the GIPHY API in the same
   > editor interface. A GIF can be clicked to insert it into their post.
7. Read the [GIPHY API Terms](https://support.giphy.com/hc/en-us/articles/360028134111-GIPHY-API-Terms-of-Service),
   check the checkbox to agree, then click **Create App**. You now have a beta key.
8. To upgrade, click **Upgrade to Production** below the key you just created in the
   Developers Dashboard.
9. On the form, for **Which API does your app utilize?** ensure only **Search** is
   selected.
10. Answer the questions about monthly users and categories, and provide your board
    URL.
11. Upload the supporting assets the form requests:
    - A **video** showing your app in action (the first **Attach File** button).
    - A **screenshot** demonstrating the features of your app and the Giphy integration
      (the second **Attach File** button).
    - A **screenshot** that includes the "Powered by GIPHY" attribution marks (the third
      **Attach File** button).
12. Click **Apply**. Your production key should be approved within **5 working days**
    (often sooner).

> **Note:** The manual provides a demonstration video and two reference screenshots
> (the feature overview and the attribution marks) for you to download and re-upload
> when applying for the production key.

---

## See also

- `docs/21-bbcode-media-editor.md` — the post editor where smilies and Giphy GIFs are
  inserted.
- `docs/08-permissions-options-phrases.md` — the options system surrounding attachment,
  censoring, and Giphy settings.
- `docs/18-navigation-display-order.md` — how display order values sort smilies.
