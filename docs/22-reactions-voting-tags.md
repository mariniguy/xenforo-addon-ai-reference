# Reactions, Content Voting & Tags

XenForo 2 ships three built-in content-engagement features: **reactions**,
**content voting**, and **tags**. This page reflects exactly what the official
manual documents about each — what they do and, where the manual says so, how
they are configured in the control panel.

> **Sources (official XenForo manual):**
> - https://docs.xenforo.com/manual/content/reactions
> - https://docs.xenforo.com/manual/content/content-voting
> - https://docs.xenforo.com/manual/content/tags

> **Scope note:** The official manual covers these features from the
> administrator/end-user perspective. It describes the reaction manager and
> editor, the voting controls and scoring, and the tag manager. It does **not**
> document add-on APIs for these systems, so neither does this page — it states
> only what the sources state.

---

## Reactions

Reactions let users express the emotion they feel towards a piece of content
with a single click. The most basic reaction is **Like**.

XenForo allows multiple reactions to be defined — such as *love*, *sad* and
*wow* — and each one may be defined as a **positive**, **negative** or
**neutral** reaction.

### Reaction score

Each reaction type may be assigned a *reaction score*. For example, **Like** is
given a score of (positive) **+1**. If ten users 'like' a post, in the absence
of any other reactions, its reaction score will be **10**. Reactions may also be
assigned neutral (**+0**), negative (**-1**) or custom scores depending on their
use case.

### Reaction manager

All currently-defined reactions are viewable through the reaction manager at
**Content > Reactions**.

Each reaction is listed showing its **graphic**, **title** and associated
**reaction score**.

Clicking a reaction or the **Add reaction** button enters the reaction editor,
where reactions can be defined and edited.

### Reaction editor

The reaction editor form is made up of two parts.

The **first part** is specific to reactions and provides input fields to define
the following for the currently-edited reaction:

| Field | Purpose |
|---|---|
| **Title** | The name of the reaction. |
| **Text color** | The color used for the reaction's title when displayed (see note below). |
| **Reaction score** | The score this reaction contributes (positive, neutral, negative or custom). |
| **Display order** | The order in which the reaction appears. |

> **Note:** The **text color** field comes into use when a user has selected a
> particular reaction for a given content item, at which point their reaction
> will be accompanied by the title of that reaction in the color defined here.

The **second part** of the reaction editor form concerns itself with defining
the **reaction graphic**. This is achieved in the same manner in which smilie
graphics are defined.

Reaction graphics can be uploaded from your computer directly via the control
panel with **XenForo 2.2 and newer**.

### About negative reaction scores

> **Warning:** While it is possible to allow users to express negative
> reactions, many community administrators will stop short of actually assigning
> a negative score to reactions.

For example, a person may react with **angry** to a post describing an
unfortunate incident, without wanting to actually penalize the author of the
post. Negative-scoring reactions also have the potential to be abused by
vindictive users trying to gang up on other users they may dislike.

By default, XenForo assigns **neutral** scores to the ostensibly negative
reactions **sad** and **angry**.

---

## Content voting

Some content types allow **content voting**, whereby users may cast an up or
down vote for the content.

Users with appropriate permissions may cast a vote — voting it **up** if they
consider it to be 'good' or **down** if it is 'bad'. The definition of 'good' or
'bad' depends on the context and the purpose for which voting has been enabled.

### Cancelling a vote

Votes may be cancelled by clicking on the voting controls for a second time.

### Vote score

Votes contribute to the **vote score** for the content, calculated by
subtracting any down-votes from its up-votes. For example, an item with ten
up-votes and two down-votes will have a vote score of **eight**. Each content
item's vote score is displayed along with its voting controls.

### In conjunction with...

Some content types attribute specific meaning to votes cast against them:

| Content type | Meaning of votes |
|---|---|
| Answer posts in **question threads** | The quality of answers can be voted up and down. |
| **Suggestions** | Votes indicate the popularity of, or support for, the suggestion. |

---

## Tags

To help organise your forum, users may add **tags** to their content. These tags
become links, allowing topics that discuss similar things to be grouped and
searched.

### Tag manager

The tag manager lists all the tags that have been applied to content within your
forum, describing:

- the **number of items** that have been tagged with each tag, and
- the **date** the tag was most recently applied.

From the tag manager you may:

- click to **view** all content tagged with a particular tag,
- **merge** tags if you decide that they are essentially the same thing, and
- **delete** tags.
