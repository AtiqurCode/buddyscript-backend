# Buddy Script — API

Laravel backend for the Buddy Script feed. Frontend is a separate React
app; this is a pure JSON API — no Blade views, no server-rendered pages.

## Stack

- **Laravel 13**, PHP 8.4, SQLite
- **Sanctum** — token auth (`Authorization: Bearer <token>`), not
  cookie/SPA mode. Frontend and backend may end up on different hosts in
  production, and token auth doesn't depend on same-site cookies to work.
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
  anyone but its author.
- `POST /api/posts` — `body` and/or `image` (at least one required),
  `visibility: public|private`
- `POST /api/posts/{post}/like` — toggle
- `GET /api/posts/{post}/likes` — who liked it, paginated

**Comments & replies**
- `GET /api/posts/{post}/comments` — top-level comments, newest first,
  each with its replies eager-loaded
- `POST /api/posts/{post}/comments` — top-level comment
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
- **Likes are one polymorphic `morphToMany`.** `likes` has `user_id` +
  `likeable_type`/`likeable_id`, reused for posts and comments (and so,
  replies) instead of a separate like system per content type. The
  `(likeable_type, likeable_id, user_id)` unique index both prevents
  double-likes and is the lookup index for count/exists queries.
- **Cursor pagination, not page numbers**, on every list endpoint.
  Offset pagination gets slower the deeper you page into a large table;
  cursor pagination doesn't, which matters once there are millions of
  rows to page through.
- **Counts and like-state are computed in the query** (`withCount`,
  `withExists`), not per-row in a resource or a loop — the alternative is
  N+1 queries across a feed of dozens of posts.
- **The `feed` image conversion runs synchronously**, against the
  package's queued-by-default setting — a new post needs its resized
  image ready the moment the create request returns, not whenever a
  queue worker next picks up the job.
- **No edit/delete endpoints.** Not in the required feature set, so they
  aren't built — including the unused Policy methods they'd need.

## Setup

```bash
composer install
cp .env.example .env   # already done in this repo
php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan storage:link
```

Served locally via Herd at `https://buddyscript-be.test`. `FRONTEND_URL`
in `.env` controls the allowed CORS origin.

## Not yet done

- Frontend integration (the React app currently runs on its own mock API
  — this backend isn't wired up to it yet)
- Rate limiting is on `/register` and `/login` only
- No automated tests yet
