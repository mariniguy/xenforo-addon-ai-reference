# Forums, Nodes & Thread Types

XenForo organizes all site content into a tree of **nodes** (categories, forums,
link-forums and pages). Forums contain **threads**, and from XenForo 2.2 onwards a
thread can take one of several **types** — discussion, article, question or
suggestion — each with its own behaviour. This doc maps the node/forum system and
the thread-type model from the official manual, with notes on where add-ons plug in.

> **Sources (official XenForo manual):**
> - https://docs.xenforo.com/manual/forums
> - https://docs.xenforo.com/manual/forums/nodes-forums
> - https://docs.xenforo.com/manual/forums/forum-thread-types
> - https://docs.xenforo.com/manual/forums/threads
> - https://docs.xenforo.com/manual/forums/discussions
> - https://docs.xenforo.com/manual/forums/questions
> - https://docs.xenforo.com/manual/forums/articles
> - https://docs.xenforo.com/manual/forums/suggestions
> - https://docs.xenforo.com/manual/forums/thread-type-tools
> - https://docs.xenforo.com/manual/forums/thread-prefixes
> - https://docs.xenforo.com/manual/forums/thread-fields
> - https://docs.xenforo.com/manual/forums/thread-prompts
> - https://docs.xenforo.com/manual/forums/thread-batch-update
> - https://docs.xenforo.com/manual/forums/thread-rss-importer

---

## Nodes: the building blocks

A **node** is XenForo's generic term for a forum or category. It is called a node
rather than a forum because it is more general than just a forum. To end users the
word "node" is never shown directly — either a node-specific term is used (such as
*forum* or *category*) or the overall list is called the **forum list**.

Nodes are organized into a tree with parents and children, and this tree forms the
general structure of the site. Various permissions and options can be defined for
each node. Nodes are set up in the **Forums** section of the control panel.

### Node types

XenForo includes four node types, each with different functionality and appearance.

| Node type | Purpose |
|---|---|
| **Category** | A container for other nodes, with little functionality of its own. Clicking a category generally takes you to a list of the nodes directly under it. |
| **Forum** | Contains threads and posts — the primary content type. Displays information about the number of threads, posts and the last post. Multiple **forum types** are supported (see below). |
| **Link forum / redirect** | Redirects the user to a URL you specify in the control panel when clicked. Useful for directing people to a particular part of your site (even a specific thread) or to any other site. |
| **Page** | Brings static content into the node tree. You define arbitrary HTML in the control panel to display in the page — useful for FAQs or a manual / knowledge base. |

> **For add-on developers:** node types are a genuine extension point. The
> behaviour of each node type is driven by a node-type *handler*, and add-ons can
> register additional node types. The four types above are what the manual
> documents out of the box. See `docs/07-handlers-content-types.md` for how content
> handlers are wired up.

### The node tree

All nodes live in a single tree. Each node has exactly one parent and can have any
number of children, nested to whatever depth you wish.

The tree is ordered by each node's **display order** value. Because that is hard to
manage one node at a time, the tree can be reorganized with a drag-and-drop system
via the **Sort** button on the node list in the control panel.

You may structure nodes however you wish, but the most common and recommended setup
is categories at the top level with content-containing nodes inside them:

```
XenForo support (category)
├── Troubleshooting (forum)
├── Styling and customization (forum)
└── XenForo manual (link forum / redirect)

Official XenForo add-ons (category)
├── Media gallery (forum)
├── Resource manager (forum)
└── Enhanced search (forum)
```

### Node-specific moderators

Individual users may be set as moderators for a specific node, and they are
automatically given moderator permissions for any child nodes as well. Moderators
have more privileges than regular users, letting them manage posted messages and
deal with undesirable content.

To set one up, open the **moderators** menu for the node, choose **add moderator**,
enter the user's name, submit, and then choose the permissions to grant.

### Node-specific permissions

Permissions can be set on nodes per-user or per-group via the **permissions** link
for a node on the node list. By default, a child node **inherits** the parent
node's permissions unless you override a value with a more specific one.

> If a user cannot view a parent node, they can never view any of its children,
> regardless of how the child's permissions are overridden.

### Example: a private staff-only forum

To create a forum visible only to chosen groups (e.g. *Administrative* and
*Moderating*), hiding it and its content from everyone else:

1. Create a new *General discussion forum* via **Forums > Nodes > Add node**, title
   it `Staff room`, and save.
2. On the node list, click the **Permissions** control for the new forum, check the
   **Private node** option at the top, and save.
3. In the User groups list, edit *Administrative*, set **View node** to **Yes** in
   the permission matrix, and save. Repeat for *Moderating*.

The forum is now accessible to those groups and completely hidden from all others.
The same pattern can restrict a forum to any group, including a group your users may
pay to subscribe to.

---

## Forum and thread types

The basic structure of a forum is a container for threads, each thread being a
collection of posts. From **version 2.2** onwards, XenForo extends that model to
support a variety of behaviours through different **thread types** and matching
**forum types**.

The available types are:

| Type | First post | Replies are… | Notable behaviour |
|---|---|---|---|
| **Discussion** | Shown first, then replies sequentially | Ordinary replies | The default, traditional thread. Can include a **poll**. |
| **Article** | Made especially prominent | Comments | Extended media/attachment/character limits and distinct styling on the first post. |
| **Question** | The question, pinned atop every page | Answers | Any answer can be voted on and one marked as the **solution**. |
| **Suggestion** | The suggestion | (votes) | Up/down votes set the thread's position in its forum. |

Each type can live in a **dedicated forum** of the matching type, and most can also
live in a **mixed-type forum** (see below). The sections that follow describe each.

> **For add-on developers:** thread types (like node types) are an extension point
> introduced with the 2.2 forum/thread-type system. Each type is backed by a thread
> type handler that controls how the first post is rendered, how replies are
> treated, and how the thread behaves when moved. Cross-reference
> `docs/07-handlers-content-types.md` for content/handler registration and
> `docs/03-entities-finders-repositories.md` for the `Thread`/`Forum` entities the
> data lives on.

---

## Discussions (and polls)

### Discussion threads

This is the default, traditional thread type, used for general, unstructured
conversation. The original post is shown first and replies follow sequentially —
if a forum shows 20 posts per page, the second page begins with the 21st post.

Discussion threads may live in a dedicated **discussion forum** or in a
**mixed-type forum** that allows them.

### Poll threads

A poll is a special kind of discussion thread that also carries a **poll** — a
multiple-choice question posed by the thread creator that other users vote on to
express their choice.

### Discussion forums

A discussion forum is the standard forum type and acts as a container for
discussion threads. It generally shows threads ordered by the date of their last
reply, most recent first. By default it contains **discussion** and **poll**
threads.

Filters narrow the thread list — for example to show only threads with no responses,
or those within a particular date range.

### Mixed-type forums

A **mixed-type forum** is a regular discussion forum that can also host other thread
types. Support for **article** and **question** threads can be added to a discussion
forum, turning it into a mixed-type forum.

To create one, create or edit a regular discussion forum in the **Admin control
panel** via the **Node editor**, and check the types you want under **Allowed thread
types**.

> Some thread types cannot live in a mixed-type forum at all — **suggestions** are
> one such type (see below).

---

## Articles

### Article threads

When a thread is less about discussion and more about imparting knowledge, an
**article thread** gives the first post special treatment. That first post has:

- extended limits on the number of embedded media items and attachments,
- a greater character limit for very long textual content, and
- different styling, to make it distinct from later posts.

Subsequent posts are treated as **comments** on the article. Once comments run
beyond a single page, a **compressed version** of the article is shown at the top of
each page (rather than only on the first page as with a discussion thread).

Article threads may be posted in **article forums** or in **mixed-type forums** that
permit articles.

### Article forums

Dedicated article forums offer article-specific display options for thread listings.

#### Preview display style

In **preview mode**, article previews appear in a masonry-style layout with a large
**cover image** and a snippet of the article text, instead of the basic listing
style used by discussion forums.

The cover image is automatically extracted from the article text — either the first
attached image embedded with the `[ATTACH]` BB code tag, or otherwise the first
linked image using the `[IMG]` tag.

> A cover image can be chosen that is *not* shown within the article body, by
> specifying a zero width and height for the image in the BB code tag.

#### Expanded display style

**Expanded mode** replicates blog-style pages: full articles displayed on a single
page, ordered chronologically. Clicking an article takes the viewer to the article's
own page, where comments may be posted.

---

## Questions

### Question threads

When a thread author seeks an answer to a specific question, they may post a
**question thread**. The first post is the question and is displayed at the top of
every page of the thread, above all replies. Replies are treated as **answers**, any
of which may become the **solution**.

Users with permission to vote on threads and posts may cast a vote on each answer,
which can help others judge which answer should be the solution.

#### Solutions

A question thread is **unsolved** until one answer is selected as the **solution**.
The thread author may select it (with permission); otherwise a forum moderator or
administrator can. Once selected, the solution post is displayed at the top of each
page alongside the question, and the thread shows as **solved** in its parent forum.

Question threads may live in a dedicated **question forum** or a **mixed-type
forum** that allows questions.

### Question forums

A question forum looks similar to a discussion forum but adds controls at the top of
the listing for easy filtering. Common filters include **Unsolved**, **Your
questions** and **Your answers**.

> Questions that have a solution are shown with an icon to distinguish them from
> unsolved questions.

---

## Suggestions

### Suggestion threads

Suggestion threads are useful for soliciting structured feedback. The first post is
the **suggestion**, and users with the appropriate permission can vote it **up or
down** depending on how they feel about it.

The **vote score** determines the suggestion's position within its parent forum —
higher-scoring suggestions appear further up the list.

> Suggestion threads may **only** be posted within dedicated **suggestion forums**.
> The forum's ordering of suggestions by vote score is crucial to how the suggestion
> thread type works, so this type cannot live in a mixed-type forum.

### Suggestion forums

Suggestion forums are the only forum type that may contain suggestion threads. Each
suggestion's vote score is displayed prominently in the listing, and threads are
ordered with higher scores before lower ones.

Filter controls narrow the list to **popular** or **new** suggestions, with options
to show only the visitor's own suggestions or those they have voted on.

---

## Thread type tools: moving between forums

Extra care is needed when an operation moves a thread from one forum to another,
because the destination forum may not support the thread's type.

- Moving a **discussion** thread into a **suggestion forum** (which can hold only
  suggestions) automatically **converts** it from a discussion to a suggestion.
- Moving a **suggestion** thread into a **discussion forum** converts it to a plain
  discussion — which causes **all votes** cast for that suggestion to be **lost**.

> **Warning:** Moving a discussion, article or question thread *out* of a dedicated
> discussion / article / question forum and *into* a mixed-type forum lets the
> thread keep its type, but it loses the forum-specific behaviours it had in the
> type-specific forum.

Suggestion threads may only be moved into suggestion forums if the special
properties of suggestions are to be retained.

> These rules for *moving* threads also apply to other operations that effectively
> move a thread between forums, such as **merging**, **copying** and **spam
> cleaning**.

> **For add-on developers:** any custom code that relocates threads programmatically
> should respect the same type-compatibility rules the manual describes here. Use
> the framework's thread move / type-conversion services rather than rewriting node
> IDs directly, so type conversions and their side effects (e.g. lost suggestion
> votes) are handled consistently. See `docs/07-handlers-content-types.md`.

---

## Thread prefixes

Thread prefixes apply pre-defined options to individual threads, displayed **before
the thread title**. The text can be styled in various ways so users can easily
identify groups of threads by prefix. Prefixes are set up under **Forums > Thread
prefixes**.

When viewing a forum, users can filter by individual prefix, which makes prefixes
powerful and able to fill many roles — for example, selecting the version a question
relates to, tracking the status of bug reports and suggestions, or selecting the
type of resource being sought.

A prefix is essentially part of the thread title: it is displayed with the title in
virtually all contexts and is indexed by search engines.

> A thread may only have a **single** prefix at any time.

### Prefix descriptions

From XenForo 2.2 onwards, prefixes may have **descriptions** that help users
understand their use. Two fields are available:

| Field | Shown | Purpose |
|---|---|---|
| **Description** | Underneath the title of a thread that uses the prefix | Clarifies for *readers* how the prefix was applied (e.g. a **Sold** prefix: *"This item has been sold and is no longer available to purchase"*). |
| **Usage help** | While the creator is selecting a prefix for a new thread | Explains each prefix to the thread *creator* before they save. |

### Availability

When configuring a prefix you control who can use it and where:

- **Usable by user groups** — if specific groups are selected, only a member of one
  of those groups can create a thread with this prefix.
- **Applicable forums** — the prefix is only usable within the chosen forums.

---

## Custom thread fields

Custom thread fields define additional **structured fields** for users to fill in
when creating a thread. Like custom user fields, they support various form input
types, including textboxes, radio buttons and checkboxes. They are set up under
**Forums > Custom thread fields**.

These fields relate to the **thread itself**, so they apply only to the initial
thread content — repliers are not prompted to enter them.

Each field value is displayed in one of several locations:

| Location | Where it appears |
|---|---|
| **Before message** | In the body of the first post, directly above the message content. |
| **After message** | In the body of the first post, directly below the message content. |
| **Thread status block** | Above the first post, in a separate small block — shown on **all** pages of the thread. |

As with prefixes, the **Applicable forums** and **Editable by user groups** options
control where the field is displayed and who can enter a value.

> **For add-on developers:** custom thread fields are a built-in way to attach
> structured data to threads without writing a schema migration. If your add-on
> instead needs its own columns or tables on the thread/forum entities, see
> `docs/03-entities-finders-repositories.md` for the entity layer and
> `docs/11-schema-migrations.md` for adding columns to core tables such as
> `xf_thread` / `xf_forum`.

---

## Thread prompts

Thread prompts control the specific **placeholder** shown when creating a thread in
a particular forum. They can encourage users to post, or lightly direct them toward
a specific sort of thread — for example, when seeking feedback, prompts like *"How
can we improve?"* or *"Let us know what you think of our product"*. They are set up
under **Forums > Thread prompts**.

- In the forum's thread list, the prompt appears in the **quick thread** input at
  the top of the list.
- On the full thread creation form, it appears within the thread **title** input
  while that input is empty.

If multiple prompts are assigned to a forum, the displayed one is chosen **at
random**. If no prompts are available for a forum, the **default** thread prompt
value is used.

---

## Batch update threads

When you need to take the same action against a large number of threads — for
example moving many threads to a different forum, or deleting all threads started by
a user — use **Forums > Batch update threads**.

1. **Identify the criteria** for the threads you want to act against.
2. To refine manually, on the next screen click **view or filter matches**, then
   select the threads you want and click **batch update**.
3. With only the desired threads selected, choose the action — including **moving**
   the threads, **setting a prefix**, and **deleting** them — and click the
   appropriate button. Changes are applied immediately, which may take some time
   depending on the number of threads and the action.

> **Delete threads** here means **hard deleting** the matched threads and removing
> them from the database. **This cannot be undone.**

---

## RSS feed importer

The RSS feed importer automatically creates threads by importing the content of RSS
feeds, helping to generate new content for discussion. It is set up via **Forums >
RSS feed importer**.

When creating a feed you must choose:

- the **forum** the threads will be posted into, and
- the **user** attributed as the thread author. If you select the **guest** option,
  the thread's name is the author value provided by the feed.

The **message template** option controls how feed content is formatted within the
thread. Any **placeholder tokens** used in the template are replaced with values
from the feed before posting.

> **Warning:** Before importing from any RSS feed, ensure you have permission to
> publish the content. You may wish to use only feeds that include a small snippet
> of the content they relate to.

---

## See also

- `docs/07-handlers-content-types.md` — node-type and thread-type handlers, content
  type registration.
- `docs/03-entities-finders-repositories.md` — the `Node`, `Forum` and `Thread`
  entities and how to query them.
- `docs/11-schema-migrations.md` — adding columns to core tables such as `xf_forum`
  / `xf_thread` from an add-on's `Setup.php`.
