# Buddy Script — API

Laravel backend for the Buddy Script feed. Frontend is a separate React
app; this is a pure JSON API — no Blade views, no server-rendered pages.

## Stack

- **Laravel 13**, PHP 8.4 — **MySQL** in production, **SQLite** locally
- **Sanctum** — token auth (`Authorization: Bearer <token>`), not
  cookie/SPA mode. Frontend and backend are on different hosts in
  production, and token auth doesn't depend on same-site cookies to work.
  Tokens expire after 30 days on their own (Sanctum checks this on every
  request — no refresh flow needed).
- **Spatie MediaLibrary** — post images

## What's implemented

**Auth**
- `POST /api/register` — first name, last name, email, password
- `POST /api/login` — returns `{ user, token }`
- `POST /api/logout` — revokes the token used for the request (not every
  device the user's signed into)
- `GET /api/user` — current user

**Feed**
- `GET /api/posts` — cursor-paginated, newest first. Public posts plus the
  viewer's own private ones — filtered in the query itself, not after
  fetching, since a private post has to never leave the database for
  anyone but its author. Each post embeds a preview of its 5 most recent
  likers and just its single latest top-level comment, so a card has
  something to show without a follow-up request.
- `POST /api/posts` — `body` and/or `image` (at least one required),
  `visibility: public|private`
- `POST /api/posts/{post}/like` — toggle
- `GET /api/posts/{post}/likes` — who liked it, paginated

**Comments & replies**
- `GET /api/posts/{post}/comments` — top-level comments, newest first,
  each with a reply count (not the replies themselves)
- `POST /api/posts/{post}/comments` — top-level comment
- `GET /api/comments/{comment}/replies` — a comment's replies, oldest
  first, loaded when the user actually expands them
- `POST /api/comments/{comment}/replies` — reply to a comment
- `POST /api/comments/{comment}/like` — toggle (same endpoint for a
  comment or a reply — they're the same model)
- `GET /api/comments/{comment}/likes` — who liked it, paginated

## Key decisions

- **Comments and replies share one table.** `comments.parent_id` is null
  for a top-level comment, set for a reply. They're identical in every
  other way (author, body, likes), so a separate `replies` table would
  just duplicate the model, policy, and like logic for no benefit.
  `post_id` is denormalized onto every row — including replies — so a
  whole thread loads in one query instead of walking `parent_id` up
  through the table.
- **Likes are one polymorphic table**, reused for posts and comments (and
  so, replies) instead of a separate like system per content type. The
  `(likeable_type, likeable_id, user_id)` unique index prevents a double
  like and is the same index that answers "does this user like this" and
  "how many likes" queries. The `likeable_type`/`likeable_id` columns are
  defined by hand rather than through Eloquent's `morphs()` helper, since
  `morphs()` also adds its own index on those two columns — one that the
  unique index above already covers, so it'd just be dead weight on every
  like/unlike.
- **The feed only ever embeds a post's latest comment, not the thread.**
  Older comments load via `GET /posts/{post}/comments` when someone clicks
  to see them, and a comment's replies load via
  `GET /comments/{comment}/replies` only once expanded. Sending every
  comment and reply with every feed page would mean paying for threads
  nobody's going to read on that visit.
- **Cursor pagination, not page numbers**, on every list endpoint.
  Offset pagination gets slower the deeper you page into a large table;
  cursor pagination doesn't, which matters once there are millions of
  rows to page through.
- **Counts and like-state are computed in the query** (`withCount`,
  `withExists`), not per-row in a resource or a loop — the alternative is
  N+1 queries across a feed of dozens of posts.
- **Every user gets a different-looking avatar.** There's no avatar-upload
  feature in scope, so `User::avatarUrl()` deterministically assigns one
  from a handful of headshot photos already bundled in the frontend's
  design assets, keyed off the user's id, instead of every account
  rendering the same placeholder image.
- **The `feed` image conversion runs synchronously**, against the
  package's queued-by-default setting — a new post needs its resized
  image ready the moment the create request returns, not whenever a
  queue worker next picks up the job.
- **Rate limiting on every authenticated route**, not just
  register/login — a logged-in client can't hammer create-post, comment,
  or like endpoints with no cap. Keyed per user, not per IP, since
  `auth:sanctum` resolves the user before the throttle check runs.
- **No edit/delete endpoints.** Not in the required feature set, so they
  aren't built — including the unused Policy methods they'd need.

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite   # local dev only — production uses MySQL
php artisan migrate
php artisan storage:link
php artisan db:seed               # optional — fake users/posts/comments/likes
```

Served locally via Herd at `https://buddyscript-be.test`. `FRONTEND_URL`
(and `FRONTEND_URLS` for extra comma-separated origins) in `.env` control
what CORS allows through.

## Deployment

Live at `https://buddyscript-be.mdatiqur.me`, on shared hosting (cPanel),
no SSH access. A GitHub Actions workflow (`.github/workflows/deploy.yml`)
FTP-deploys the app on every push to `master`. Since there's no SSH to run
`artisan migrate` after a deploy, a secret-gated `GET /deploy` route
(`DeployController`, guarded by `DEPLOY_SECRET`) runs it remotely instead —
registered outside the normal route groups so it works even before the
database has been migrated for the first time.

## Not yet done

- No automated tests yet
