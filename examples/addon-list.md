# XenForo Free Addon Reference List

All 10 addons below were downloaded from xenforo.com/community/resources/ using the `/download?version=latest` endpoint. All confirmed valid ZIP archives.

| Addon | Author | What it does | URL | Key patterns | Downloaded |
|-------|--------|--------------|-----|--------------|------------|
| [Tinhte] XenTag | Tinhte | Hashtag/tag system for threads, resources, media | [xenforo.com/.../tinhte-xentag.770](https://xenforo.com/community/resources/tinhte-xentag.770/) | ContentWrapper abstraction, BbCode auto-formatter, Model extension (XF\Model\Tag), DataWriter\TagWatch, search integration | Yes |
| [8WR] XenPorta (Portal) | 8wayRun.Com | Full portal/front-page system with block widget layout engine | [xenforo.com/.../8wr-xenporta-portal.90](https://xenforo.com/community/resources/8wr-xenporta-portal.90/) | Route\Interface implementation, versioned installCode/uninstallCode pattern, block plugin system, DataWriter with allowedValues enum, ControllerPublic extension | Yes |
| [8WR] XenAtendo (Events) | 8wayRun.Com | Community events calendar with RSVP and recurring events | [xenforo.com/.../8wr-xenatendo-events.99](https://xenforo.com/community/resources/8wr-xenatendo-events.99/) | DataWriter with \_preSave visitor injection, moderation queue integration, CronEntry for recurring events, ControllerPublic sub-routes | Yes |
| [8WR] XenMedio (Media) | 8wayRun.Com | Media gallery with categories, playlists, comments, alert handlers | [xenforo.com/.../8wr-xenmedio-media.97](https://xenforo.com/community/resources/8wr-xenmedio-media.97/) | AlertHandler per content type, multi-level ControllerPublic routing (Media/Category, Media/Comment, etc.), Account controller extension | Yes |
| [8WR] XenCarta (Wiki) | 8wayRun.Com | Full wiki system with page history, diff, attachments, watch | [xenforo.com/.../8wr-xencarta-wiki.98](https://xenforo.com/community/resources/8wr-xencarta-wiki.98/) | AttachmentHandler\_Abstract implementation, AlertHandler, CronEntry for cleanup, DataWriter\History for revision tracking | Yes |
| XenAPI | Contex | PHP REST API wrapper exposing XenForo data over HTTP | [xenforo.com/.../xenapi-xenforo-php-rest-api.902](https://xenforo.com/community/resources/xenapi-xenforo-php-rest-api.902/) | Single-file REST API pattern, XenForo bootstrap from external PHP, hash-based authentication | Yes |
| [bd] API | xfrocks | Full OAuth2 REST API for XenForo (XF1) | [xenforo.com/.../bd-api.1732](https://xenforo.com/community/resources/bd-api.1732/) | ViewRenderer\Json + ViewRenderer\Xml, Model extension pattern (Extend/ namespace), large multi-resource API architecture | Yes |
| [XFR] User Albums | XF-Russia | Photo album system with thumbnails, comments, alerts, likes | [xenforo.com/.../xfr-user-albums.54](https://xenforo.com/community/resources/xfr-user-albums.54/) | AlertHandler per content type (Album, Comment, Image), CacheRebuilder pattern, ControllerHelper\DataRebuild, sprite thumbnail generation | Yes |
| FAQ Manager by Iversia | Iversia | Q&A FAQ with categories, likes, search, attachments, moderation | [xenforo.com/.../faq-manager-by-iversia.1413](https://xenforo.com/community/resources/faq-manager-by-iversia.1413/) | Full CRUD DataWriter with attachment association, search indexer integration, moderation queue insert, permission checks in Model, LikeHandler, getSessionActivityDetailsForList | Yes |
| Smiley Manager | Milano | Per-user custom smiley sets, editor plugin integration | [xenforo.com/.../smiley-manager.2538](https://xenforo.com/community/resources/smiley-manager.2538/) | Listener with initDependencies for template helper registration, editor plugin JSON options, addRequiredExternal for JS, DataWriter\User extension, Install with table patch (ALTER column) | Yes |

## Notes on download URL format

The working URL pattern for all 10 addons was:

```
https://xenforo.com/community/resources/{slug}.{id}/download?version=latest
```

Authentication required: `xf_user` and `xf_session` cookies. Resources that require purchase return the purchase page HTML instead of a ZIP — check the magic bytes (`PK` = valid ZIP).
