# Buddy Script — API

Laravel backend for the Buddy Script feed. The frontend is a separate React
app; this is a pure JSON API with no Blade views and no server-rendered
pages.

## Stack

Laravel 13 on PHP 8.4. MySQL in production, SQLite for local dev. Auth is
Sanctum bearer tokens (`Authorization: Bearer <token>`) rather than
cookie/session auth — the frontend and backend live on different hosts in
production, and token auth doesn't need same-site cookies to work. Tokens
expire after 30 days on their own; Sanctum checks that on every request so
there's no refresh flow to build. Post images go through Spatie
MediaLibrary.

## What's implemented

**Auth**
- `POST /api/register` — first name, last name, email, password
- `POST /api/login` — returns `{ user, token }`
- `POST /api/logout` — revokes the token used for the request, not every
  device the user's logged into
- `GET /api/user` — current user

**Feed**
- `GET /api/posts` — cursor-paginated, newest first. Public posts plus the
  viewer's own private ones, filtered in the query itself rather than
  fetched then filtered, since a private post should never leave the
  database for anyone but its author. Each post comes with a preview of its
  5 most recent likers and its single latest top-level comment, so a card
  has something to show without a follow-up request.
- `POST /api/posts` — `body` and/or `image` (at least one required),
  `visibility: public|private`
- `POST /api/posts/{post}/like` — toggle
- `GET /api/posts/{post}/likes` — who liked it, paginated

**Comments & replies**
- `GET /api/posts/{post}/comments` — top-level comments, newest first, each
  with a reply count (not the replies themselves)
- `POST /api/posts/{post}/comments` — top-level comment
- `GET /api/comments/{comment}/replies` — a comment's replies, oldest first,
  loaded only once the user actually expands them
- `POST /api/comments/{comment}/replies` — reply to a comment
- `POST /api/comments/{comment}/like` — toggle. Same endpoint handles a
  comment or a reply, since they're the same model under the hood.
- `GET /api/comments/{comment}/likes` — who liked it, paginated

## Key decisions

Comments and replies share one table instead of two. A `comments` row with
`parent_id` null is a top-level comment, and with it set it's a reply —
otherwise they're identical (author, body, likes), so splitting them out
would just duplicate the model, the policy and the like logic for nothing.
`post_id` is denormalized onto every row, replies included, so a whole
thread can load in one query instead of walking `parent_id` back up the
table.

Likes are one polymorphic table too, reused across posts, comments and
replies instead of three separate like systems. The unique index on
`(likeable_type, likeable_id, user_id)` stops a double like and also serves
as the lookup index for every count/exists query. The `likeable_type` and
`likeable_id` columns are defined by hand rather than through Eloquent's
`morphs()` helper — `morphs()` adds its own index on those two columns, and
since the unique index above already covers the same lookup, that second
index would just be dead weight on every like and unlike.

The feed only ever embeds a post's latest comment, never the whole thread.
Older comments come from `GET /posts/{post}/comments` when someone clicks to
see them, and a comment's replies come from `GET /comments/{comment}/replies`
only once expanded — sending every comment and reply with every feed page
would mean loading conversations nobody's about to read.

Pagination is cursor-based on every list endpoint, not page numbers. Offset
pagination gets slower the deeper you page into a large table; cursor
pagination doesn't, and that starts to matter once there are millions of
rows instead of a few hundred.

Counts and like state are computed inside the query with `withCount` and
`withExists`, not per-row in a resource or a loop, since the alternative is
an extra query per post per page of the feed.

Every user gets a different-looking avatar. There's no avatar upload
feature in scope, so `User::avatarUrl()` just deterministically hands out
one of a handful of headshot photos already bundled in the frontend's
design assets, based on the user's id, instead of every account showing the
same placeholder.

The `feed` image conversion runs synchronously, against the package's
queued-by-default setting, because a new post needs its resized image ready
the moment the create request returns — not whenever a queue worker
eventually picks the job up.

Every authenticated route is rate-limited now, not just register and login,
so a logged-in client can't hammer create-post, comment or like endpoints
with no cap. It's keyed per user rather than per IP, since `auth:sanctum`
resolves who the user is before the throttle check runs.

There's no edit or delete anywhere. Not part of the required feature set,
so no endpoints, no UI, and no unused policy methods sitting around for
either.

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite   # local dev only — production runs MySQL
php artisan migrate
php artisan storage:link
php artisan db:seed               # optional: fake users/posts/comments/likes
```

Runs locally via Herd at `https://buddyscript-be.test`. `FRONTEND_URL` (and
`FRONTEND_URLS` for extra comma-separated origins) in `.env` control what
CORS lets through.

## Deployment

Live at `https://buddyscript-be.mdatiqur.me`, on shared cPanel hosting with
no SSH access. A GitHub Actions workflow (`.github/workflows/deploy.yml`)
FTP-deploys on every push to `master`. Since there's no SSH to run
`artisan migrate` after that, a secret-gated `GET /deploy` route
(`DeployController`, guarded by `DEPLOY_SECRET`) does it remotely instead.
It's registered outside the normal route groups on purpose, so it still
works even before the database has been migrated for the first time.

## Not yet done

No automated tests yet.
