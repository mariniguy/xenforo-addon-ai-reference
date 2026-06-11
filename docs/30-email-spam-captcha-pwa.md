# Email, Spam, CAPTCHA & PWA

Site protection and delivery configuration for a XenForo 2.3 board: how email is
sent, how guests are challenged with CAPTCHA, the suite of anti-spam tools (and
the Spam cleaner), and the Progressive Web App (PWA) settings that turn the forum
into an installable mobile app. These are admin-configured features — an add-on
typically integrates with them rather than reimplementing them.

> **Sources (official XenForo manual):**
> - Email — https://docs.xenforo.com/manual/configuration/email
> - CAPTCHA — https://docs.xenforo.com/manual/configuration/captcha
> - Spam management — https://docs.xenforo.com/manual/configuration/spam
> - The spam cleaner — https://docs.xenforo.com/manual/configuration/spam/spam-cleaner
> - XenForo PWA — https://docs.xenforo.com/manual/configuration/pwa

All settings below live in the Admin control panel (ACP). Most are under
**Options**; CAPTCHA question authoring and PWA setup live under **Setup**.

---

## Email

Immediately after installation, XenForo can send email on most servers using
PHP's default mailing settings. To control more aspects of the mail XenForo
sends, go to **Options** and open the **Email options** group.

### Transport configuration

The *Email transport method* controls the approach to sending mail. XenForo
supports three methods:

| Method | What it does | Trade-offs |
|---|---|---|
| **PHP built-in mail** | Uses the default configuration of PHP to send email. | Generally preferred — it offloads the actual act of sending the mail to a dedicated program on the server, giving better performance. |
| **SMTP** | Uses an outside server to send emails. | In some situations can reduce the likelihood of mail being seen as spam, and gives a lot of flexibility. Slower than the default method, because each mail is sent by XenForo rather than handed off to a dedicated program. |
| **Google OAuth** | Authenticates to Google's mail servers with an OAuth token instead of standard SMTP credentials. | Google are moving towards a time when they will not accept standard SMTP credentials and will instead expect an OAuth token as a security measure. |

> **Google OAuth setup:** navigate to Google's
> [Developer Console](https://console.developers.google.com/) and set up a new
> project with OAuth 2.0 credentials for a web application. Step-by-step
> instructions are available on-screen as part of the OAuth setup process within
> XenForo. See also the XenForo community thread
> [More info regarding OAuth options](https://xenforo.com/community/threads/assorted-improvements.181954/post-1437261).

### Additional options

There are several additional email options you should consider setting:

- **Default email address** — Most emails sent from your XenForo installation
  appear to be sent by this account. This must be a valid email address.
- **Bounced email address** — When an email cannot be delivered, a message
  indicating this will be sent to the address you specify here. If you don't
  specify anything, it will go to your **Default email address**.
- **Default email sender name** — Normally emails sent via XenForo will have a
  sender name of your **Board title**. This option can override that with a more
  reasonable name.

---

## CAPTCHA

XenForo includes support for reCAPTCHA and custom CAPTCHA questions, used as a
spam-prevention measure for guests.

### Selecting a CAPTCHA type

Select your CAPTCHA type from the options:

1. Log in to the admin control panel.
2. Select **Options**.
3. Select **Basic board information**.
4. Locate the **Enable CAPTCHA for guests** section and select the CAPTCHA type.

### Question and answer CAPTCHA

Included within XenForo is a bespoke CAPTCHA system which can be used as an
additional spam-prevention measure. If enabled, it requires questions to be
answered correctly for new registrations and, if allowed, guests posting
messages. This helps to prevent robots from registering and creating content.

#### Creating custom questions and answers

1. Log into the admin control panel.
2. Select the **Setup** section.
3. Click on **Q&A CAPTCHA** from the list.

Once at the main screen, click on the **Add question** button. Questions can be
created with as many answers as you wish, any one of which will be accepted as
the correct response. Visiting users who need to complete a CAPTCHA will be
presented with a random item from the list of active questions.

> **Note:** It is imperative that you do not make the list of questions and
> answers public, as that will compromise the integrity of the system.

---

## Spam management

XenForo includes a suite of tools designed to prevent, combat, and manage spam.
The settings described here are found in the **Spam management** options group.

### Spam prevention — registrations

Several tools can be used to prevent spammers from registering. These can all be
found in the **Spam management** options.

- **StopForumSpam** — The [StopForumSpam](http://www.stopforumspam.com/) database
  can be checked. This is a collaborative database used by thousands of forums to
  prevent known spammers from registering. The integration behavior is tunable
  based on the confidence of the database result. If you register and request a
  **StopForumSpam API key**, you can submit spam information back to
  StopForumSpam whenever you ban a spammer using the spam cleaner.
- **DNS block lists** — Check one of several DNS block lists, such as the
  *Tornevall DNSBL*. These simply check the IP of the user registering against
  known spam IPs and take an action against them.
- **Registration timer** — A weak defense against spam, but it can catch out
  some automated scripts and prevents them from submitting the forms too quickly.
  Setting this value too high may affect human users.

### Spam prevention — content

A spammer may manage to bypass the automated registration checks and successfully
register. A second line of defense can be added to prevent them from submitting
their spam content. These options are also found in the **Spam management** option
group.

- **Spam phrases** — Spam phrases can be defined. If any of a user's first few
  messages matches these phrases, an action can be taken. For example, some
  spammers submit messages with "watch *film name* online"; you can match that
  with `watch * online` and simply block the message.
- **Akismet** — For more dynamic content matching,
  [Akismet](https://akismet.com/) can be checked. This service uses heuristics to
  determine if the submitted content is spam. If Akismet thinks the content is
  spam, the content will be placed into your forum's moderation queue and you
  will need to manually approve (or delete) the message before it is displayed to
  normal visitors.

> The spam-phrase example uses a wildcard: `watch * online` matches any message
> containing "watch", later "online", with anything in between. This is how a
> single phrase catches many variations of the same spam template.

### CAPTCHA as a spam control

There are two CAPTCHA systems available, only one of which can be used at any one
time. They work by requiring visitors to carry out tasks that are difficult for
machines to perform (in the case of reCAPTCHA), or by answering specific
questions (the Question & Answer CAPTCHA).

If you use the XenForo question-and-answer CAPTCHA system, you will need to
define a set of questions and answers which visitors have to answer correctly
when registering or posting messages, if guest posting is allowed. This is done
by clicking on **Q&A CAPTCHA** in the **Setup** section of the ACP (see the
[CAPTCHA](#captcha) section above).

---

## The spam cleaner

XenForo includes a tool for use directly on user-generated content, called the
Spam cleaner. Its purpose is to quickly and efficiently deal with any spam posted
to your forum with just a few clicks.

Content that is eligible for spam cleaning will have a **Spam** link near its
normal **Edit** and **Delete** controls.

### Configuring the Spam cleaner

1. Log in to the Admin control panel.
2. Click on **Options** from the *Setup* section of the navigation panel.
3. Select the **Spam management** group from the list.

There are several sections on the resulting page which work in conjunction to
help keep your site free of unwanted visitors and content:

- The Spam Cleaner can be made available for use on members based on message
  count, elapsed days since registering, and the number of Likes that member has
  received. This is configured via the **Spam cleaner user criteria** option.
- For any members who do not meet the criteria — by having a higher message or
  like count, or having been registered for more days than the set limit — the
  spam cleaner will not be available. It will be necessary to increase the values
  to make it available for those members.
- The **default options** control which checkboxes are already selected when
  running the Spam Cleaner. The checkboxes can be selected or deselected each
  time it is run, regardless of the settings here.
- The **actions** to be taken with affected threads and messages include
  permanently deleting them, removing them from public view, and — in the case of
  threads — moving them to a specific forum.
- The **default email text** entered here can also be edited each time the Spam
  Cleaner is run.
- The **IP check** will return any matches from other members, for the past
  number of days specified.

> **Note:** To make the Spam Cleaner available at all times for all content
> regardless of its author or age, set all three **Spam cleaner user criteria**
> options to `0`.

### Using the Spam cleaner

To use the spam cleaner, a user must have the appropriate spam cleaner permission
enabled. This can be done by way of user group or user permissions (see the
**Permissions** section of the manual). The Spam cleaner can be run from several
locations:

- On a thread or profile post, by clicking the *Spam* link near the *Edit* and
  *Delete* controls.
- On a member popup, by clicking the *Spam* link in the tools menu.
- On a profile page, by clicking the *Spam* link in the Moderator tools menu.

Clicking any of those links results in a *Spam cleaner* overlay from where you can
select the actions to be taken. This can range from a simple IP check to a
permanent ban and removal of all content.

> **Note:** Banned users do not automatically show as being banned, nor do they
> have any specific markup applied to their user name or title. Refer to the
> **Discouraging** and **Banning** sections of the manual for further
> information.

### Restoring deleted content

If you wish to restore any content deleted as a result of using the Spam cleaner,
you can do so using the **Restore** option:

1. Open the **Tools** section of the admin control panel.
2. Click on **Spam cleaner log**.

From here you can review all content deleted by the spam cleaner and restore it
selectively, by clicking the **Restore** link for the member in question and then
the **Restore data and user status** button.

> **Note:** It is not possible to restore content which has been **permanently
> deleted**.

---

## XenForo PWA

Rather than requiring users to download a separate app from a third-party app
store, XenForo automatically reconfigures itself into a mobile-optimised mode
called a *progressive web app* (PWA).

### Features of the PWA

- **Responsive design** — When the system detects a small browser viewport, as
  found on mobile devices, the user interface automatically adjusts into a
  space-optimised configuration. Multi-column layouts are replaced with single
  columns, clickable controls are made larger to facilitate touch-based browsing,
  and secondary controls that would otherwise clutter the interface are relocated
  into menus and other visible-on-demand elements.
- **Installability** — Like a native mobile app, the PWA allows your forum to be
  *installed* in an app-like manner on users' devices, appearing as an app icon
  that launches without going via the device's web browser. Apple mobile devices
  running **iOS 16.4 and newer** can install the PWA using the *Add to Home
  Screen* feature in Safari — this step is required in order to enable push
  notifications on iOS devices.
- **Push notifications** — XenForo's alerts system integrates with the device's
  built-in notification system to provide push notifications that appear even
  when users are not actively browsing the forums. Availability depends on the
  user's specific device, but all tier-one devices are currently supported.
- **App badging** — As an installed app, a counter of active notifications
  appears as a badge on the app icon.
- **Share sheet integration** — When users click the *share* controls to share
  content from your forum, XenForo triggers the built-in system share sheet for
  the user's device, providing a familiar native interface.

### Requirements

The following are absolute requirements for XenForo to enable its PWA
functionality:

1. Your site **must** be accessible over HTTPS / SSL.
2. You **must** provide a title for your site of **12 or fewer** characters.
3. You **must** provide a pair of square icons for your site, one at **192**
   pixels width and one at **512** pixels.

### Configuration

The responsive design elements require no configuration and activate
automatically on any small-screen device. The remaining PWA technologies require
some graphical assets and configuration. All relevant settings are located in the
ACP at **Setup > PWA setup**.

- **Board title** — If your main *Board title* is longer than 12 characters, you
  must enter a shortened version in the *Board short title* field.
- **Enable push notifications** — You may enable push notifications provided your
  server has **PHP 7.1 or newer** with the
  [gmp](https://secure.php.net/manual/en/book.gmp.php),
  [mbstring](https://secure.php.net/manual/en/book.mbstring.php) and
  [openssl](https://secure.php.net/manual/en/book.openssl.php) extensions enabled.
  XenForo Cloud sites meet and exceed these requirements.
- **Language** — You must choose one of the languages installed on your forum to
  be the primary language for the PWA. Options are provided to make the selection.
- **Colors** — While the styling of your PWA is based on the settings of your
  default style, you may provide additional styling for the *Meta theme color*
  (which often colors the device interface itself) and the *Page background color*
  (upon which all content is placed).
- **Icons** — You must provide the URLs of, or upload using the provided tools,
  two square icons for the app.

#### Icon details

These icons must measure **192x192** pixels and **512x512** pixels. As a general
rule, you should use **PNG** image format.

Ideally, these icons should be *maskable*, meaning that any vital graphical
elements within them fit within the *minimum safe zone* when the icons are
cropped by differing devices. If your icons are indeed maskable, check the box to
confirm this. See
[Full details of maskable icons at web.dev](https://web.dev/maskable-icon/).

| Asset | Requirement |
|---|---|
| HTTPS / SSL | Mandatory for the PWA to be enabled at all |
| Board short title | 12 characters or fewer |
| Icon (small) | 192x192 px square, PNG recommended |
| Icon (large) | 512x512 px square, PNG recommended |
| Push notifications | PHP 7.1+ with `gmp`, `mbstring`, `openssl` extensions |
| iOS push notifications | iOS 16.4+, PWA installed via *Add to Home Screen* in Safari |

---

## Notes for add-on developers

- These are **admin-configured** site features. An add-on integrates with the
  existing email, CAPTCHA, spam, and PWA subsystems rather than reimplementing
  them; the official manual pages above describe only the admin-facing
  configuration.
- Email transport, CAPTCHA provider selection, spam controls (StopForumSpam,
  DNSBLs, registration timer, spam phrases, Akismet), and PWA assets are all set
  through **Options** / **Setup** in the ACP, not through add-on code.
- The Spam cleaner's availability is gated by both a **permission** and the
  **Spam cleaner user criteria** (message count, days registered, likes
  received). Setting all three criteria to `0` makes it available for all content.

**See also:** `docs/08-permissions-options-phrases.md` (defining permissions and
options), `docs/09-criteria-system.md` (user/content criteria).
