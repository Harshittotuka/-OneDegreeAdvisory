# One Degree Advisory — SEO & Search Visibility Plan

**Goal:** Make `onedegreeadvisory.com` appear in Google — especially for **"one degree advisory"** and study-abroad queries — and, over time, outrank the unrelated US firm **"One Degree Advisors"** (San Diego financial advisory, est. 2001, `onedegreeadvisors.com`) that currently dominates the brand phrase.

This file has two parts:
1. **What was already fixed in code** (done — shipped to the live site).
2. **What you must do off-site** (Google can't be forced by code — these are the actions that actually earn rankings).

---

## Part 1 — Fixed in code (already live)

These were the technical blockers. All done:

| Fix | Why it mattered |
|---|---|
| **Canonical host unified to non-www** (`https://onedegreeadvisory.com`) — forced in `AppServiceProvider`, matched in `robots.txt` | Before, the app generated non-www URLs while robots/canonical claimed www. Google saw two competing sites and split ranking signals. Now every canonical tag, sitemap URL, OG tag, and JSON-LD `@id` uses one origin. |
| **LocalBusiness + geo structured data** (Jaipur address + coordinates) added to the homepage JSON-LD | This is the single strongest signal that you are a **Jaipur education business**, a different entity from the San Diego *financial* firm. Helps Google Maps / local results. |
| **Test site (`*.nip.io`) forced to `noindex` on every page** | The UAT box runs as `production` and was fully indexable — a duplicate of the live site that would compete with it. Now every page on any non-canonical host (nip.io, raw IP, localhost) emits `<meta name="robots" content="noindex, nofollow">`, which is the authoritative signal Google obeys. (Note: a server-layer robots.txt block was also added via `.htaccess`, but LiteSpeed serves the static `robots.txt` directly and skips `.htaccess` rewrites, so on this host the meta tag is what does the work — verified live on `/`, `/study-abroad`, `/contact`, `/blog`.) |
| **`google-site-verification` meta wired up** (was already in code; value still needs setting — see below) | Required to connect Google Search Console. |

Existing strengths confirmed (no change needed): keyword-rich `<title>`s on every page ending in "| One Degree Advisory", valid dynamic sitemap (67 URLs), per-page meta descriptions, OG/Twitter cards, `EducationalOrganization` + `WebSite` JSON-LD, `knowsAbout`/services structured data.

---

## Part 2 — Off-site actions (DO THESE — in priority order)

> Reality check: the code is now correct, but a brand-new site with no authority cannot outrank a 25-year-old firm for its near-exact name overnight. The phrase "one degree advisory" will take **months** of the work below. Study-abroad + Jaipur queries are winnable much sooner.

### 🔴 Priority 1 — Get indexed (do this week, free)

1. **Google Search Console** — https://search.google.com/search-console
   - Add property: `https://onedegreeadvisory.com` (URL-prefix) — or, better, the **Domain** property (verifies via DNS, covers www + non-www + http/https).
   - Verify ownership:
     - **DNS method (recommended):** add the TXT record Google gives you to the domain's DNS.
     - **OR HTML meta method:** Google gives you a code like `abc123…`. Send that code to your developer to set as `GOOGLE_SITE_VERIFICATION` in the live `.env` (the meta tag is already wired up and will appear automatically).
   - **Submit the sitemap:** in Search Console → Sitemaps → enter `sitemap.xml` → Submit.
   - **Request indexing:** URL Inspection tool → paste `https://onedegreeadvisory.com/` → "Request indexing". Repeat for 5–10 key pages (study-abroad, contact, top country pages).

2. **Bing Webmaster Tools** — https://www.bing.com/webmasters — add the site, submit the same sitemap. (Quick win; also feeds other engines.)

3. **Confirm indexing after ~3–7 days:** Google search `site:onedegreeadvisory.com`. Pages should start appearing.

### 🔴 Priority 2 — Google Business Profile (the biggest lever for the brand query)

This is what makes you show up as a *distinct business* from the US financial firm, with a map pin, reviews, and a knowledge panel.

1. Create/claim at https://business.google.com using the Jaipur office:
   - **Name:** One Degree Advisory
   - **Address:** A-16A, Van Vihar Colony, Tonk Road, Jaipur, Rajasthan 302018
   - **Phone:** +91 8233365888
   - **Website:** https://onedegreeadvisory.com
   - **Category:** "Educational consultant" (primary) + "Education center"
2. Verify it (Google mails a postcard or offers phone/video verification).
3. **After verification:** get the exact map-pin URL → send it to your developer to set as `GOOGLE_MAPS_PLACE_URL` in `.env` (the JSON-LD `hasMap` + exact `geo` will then point at the real pin). Also update `config/site.php` → `contact.geo.lat/lng` to the precise pin coordinates.
4. Add photos, hours, services, and a description mentioning "study abroad consultants in Jaipur".
5. **Ask happy students/parents for Google reviews.** Reviews are a top-3 local-ranking factor and a powerful brand-distinction signal.

### 🟠 Priority 3 — Build authority (backlinks + citations)

Authority = other reputable sites linking to you. New domains have ~none. Build steadily:

- **Local/India business directories (NAP citations — keep Name/Address/Phone identical everywhere):** JustDial, Sulekha, IndiaMART, Google Maps, Bing Places, Apple Maps, Yelp.
- **Education-specific directories:** Shiksha, CollegeDekho, Yocket, study-abroad listing sites — list the consultancy.
- **Social profiles (already in your JSON-LD `sameAs`):** make sure Instagram/Facebook/LinkedIn/WhatsApp are all **active, complete, and link back to the site**. Post regularly.
- **Content/PR:** publish useful blog posts (you have a blog CMS) targeting real queries — "MBBS in Georgia for Indian students", "Cost of studying in Canada 2027", "IELTS vs PTE for UK visa". Each is a page that can rank and earn links.
- **Partnerships:** universities, coaching centers, school counselors — ask for a link from their "partners"/"resources" page.

### 🟠 Priority 4 — Keyword strategy (what to actually target)

Don't burn effort fighting for the bare phrase first. Win the winnable, which also builds the authority that *eventually* wins the brand phrase.

- **Win now (low competition, high intent):**
  - "study abroad consultant in Jaipur", "overseas education consultant Jaipur"
  - "MBBS abroad consultant Jaipur / Rajasthan"
  - "One Degree Advisory" + qualifier ("…Jaipur", "…study abroad", "…reviews")
- **Win mid-term:** "study in [country] from India", "[country] student visa consultant", per the country pages you already have.
- **Win long-term:** the bare **"one degree advisory"** — achieved by accumulating brand searches, reviews, backlinks, and the Business Profile above. As Google sees more people seeking *your* ODA, your entity rises for the phrase.

### 🟢 Priority 5 — Ongoing hygiene

- In Search Console, watch **Coverage/Indexing** for errors and **Performance** for which queries bring impressions/clicks.
- Keep publishing blog content (cadence matters more than length).
- Keep NAP identical across every listing.
- Re-check `site:onedegreeadvisory.com` monthly to confirm page count is growing.

---

## Part 3 — AIOSEO site audit, 21 Sep 2026 (score 84/100)

The audit raised **4 critical issues**. Here is what each one actually was and what was done.

### ✅ Fixed — "The meta description is 165 characters, which is too long"

Real, and it was not only the homepage. The audit only scans the page you point it at; checking the whole site found **12 pages** over the 160 characters Google renders:

| Page | Was | Now |
|---|---|---|
| `/statement-of-purpose` | 207 | 157 |
| `/terms-and-conditions` | 202 | 147 |
| `/visa` | 190 | 156 |
| `/study-abroad` | 188 | 148 |
| `/referral-program` | 188 | 150 |
| `/services/test-preparation` | 183 | 150 |
| `/profiler` | 181 | 159 |
| `/services/student-services` | 176 | 150 |
| `/career-counselling` | 172 | 158 |
| `/visa-mock-interview` | 167 | 158 |
| `/` | 165 | 148 |
| `/student-development-programme` | 162 | 155 |

Two things were wrong underneath. The descriptions themselves were too long, **and** the site's own safety net was set to 170 characters rather than 160 — so every one of these was being chopped at 170 *in the middle of a word* before Google ever saw it. The Statement of Purpose page, for instance, was reaching the SERP as "…one advisor, one consistent story, from first line to f".

Both halves are fixed. The cap is now 160 everywhere (including CMS-authored blog, brief and country descriptions), and when a description does still have to be shortened it now cuts on a word boundary and drops any comma left dangling at the end.

`tests/Feature/MetaDescriptionLengthTest.php` now checks every public page on each run — that the description fits, and that it reads as a finished sentence rather than a truncated one — so this cannot come back unnoticed.

**One content edit is still yours to make:** the `/europe` page's description is written in the Page Builder, not in the code, so it is not something a developer can fix from the repo. On the copy checked here it runs long and is cut to "…visa support, with an Admission". Open that page in the CMS and shorten its meta description to 160 characters or fewer. (Worth spot-checking the other CMS-authored brief, blog and country pages while you are there.)

### ⚪ No action — "Some images on the page have no alt attribute (31)"

**This is a false positive and the markup should be left as it is.** Every one of the 31 images listed has an `alt` attribute; it is deliberately *empty* (`alt=""`), which is the correct, accessibility-standard way to mark a decorative image. AIOSEO's checker counts an empty `alt` as a missing one. The 31 break down as:

- **21 country flags** in the navigation dropdown — each sits next to the country's name in text, inside a container marked `aria-hidden="true"`. Giving them alt text would make a screen reader read every country twice ("Australia, flag of Australia, Australia").
- **8 photos** — the homepage hero backdrop and the 7 in the scrolling strip above the footer. All are pure decoration behind or beside text, inside `aria-hidden="true"` containers.
- **2 logos** — header and footer. Both are inside links that already carry `aria-label="One Degree Advisory home"`, so the link is properly named; alt text would duplicate it.

The homepage's *content* images — the six destination and advisory photos — do carry real descriptive alt text, and AIOSEO correctly did not flag them. Adding alt text to the other 31 to satisfy the checker would be keyword stuffing and a genuine accessibility regression, and would lower the real score Google cares about to raise a cosmetic one.

### 🟠 Outstanding — "The page makes 42 requests" (35 images, 4 JS, 3 CSS)

Real and worth doing, but it is a bigger change and needs a decision. **21 of the 35 images are the navigation's country flags**, each fetched individually from `flagcdn.com` — a third-party server. They load on *every page of the site*, not just the homepage.

The fix already exists in this codebase: the Referral Program page had the same problem (23 flags from flagcdn) and now uses **one 21 KB local sprite image** (`public/assets/referral/flags.webp`) instead. Applying that to the navigation would cut roughly 20 requests from every page and remove a third-party dependency from the critical path.

The reason it has not been done: the navigation's country list is **managed in the CMS** (destinations, MBBS countries, and the visibility toggles), whereas the referral sprite is a fixed, hand-maintained set. So it needs a small sprite-regeneration step that runs when a country is added or made visible, or the nav will show the wrong flag. That is the work to scope — the change itself is straightforward.

### 🟡 Marginal — "Response time is 0.23s (recommended 0.2s)"

A 30-millisecond gap, measured by AIOSEO's crawler from outside, so it includes network distance to the Hostinger VPS. Config and routes are already cached on both boxes and opcache is on. There is no obvious win here that does not carry more risk than the 30 ms is worth; a CDN in front of the site would fix it properly if the number matters to you. Not recommended as a priority over Part 2.

---

## Part 4 — Page speed, 21 Sep 2026

Measured first, so the effort went where the time actually was. From India the homepage answers in 130–155 ms, of which ~75 ms is the TCP + TLS handshake — the server itself thinks for about 60 ms. **The server was never the problem; the weight in the browser was.**

### Done

| Change | Effect |
|---|---|
| **Lucide icon subset.** The icon library shipped ~1500 icons on every page (404 KB raw, 91 KB compressed) for the ~220 this site uses. `scripts/build-lucide-subset.mjs` now bundles just those, using Lucide's own runtime. | **404 KB → 54 KB** raw, **91 KB → 14 KB** compressed, on every page |
| **Nav country lists cached.** Every page decoded a 632 KB and a 260 KB JSON file to build the Destinations and MBBS dropdowns. Now cached, keyed on those files' timestamps so a country sync or a visibility toggle still shows immediately. | ~6 ms off every request; country pages decoded it twice, now once |
| **Unused font weights dropped.** Jost and Outfit were each loaded at four weights; the site uses two of each. | 4 fewer font variants per page |
| **Titles** — see below. | — |

**The icon subset cannot break an icon.** CMS editors can type any Lucide name into a brief block, so the bundle watches for a name it does not carry and pulls in the full library by itself when it meets one. `npm run verify:icons` proves this against real rendered pages — all 10 sampled pages render every icon from the subset, and the fallback is tested too. Re-run `npm run build:icons` after adding icons, so new ones are in the fast path rather than the fallback.

### Titles

Part 1 above claims every page ends in "| One Degree Advisory". Eight had stopped doing so (`/visa`, `/profiler`, `/referral-program`, `/statement-of-purpose`, `/career-counselling`, `/loan-accommodation`, `/visa-mock-interview`, `/student-development-programme`) and two ran past the ~60 characters Google displays. All 25 public pages now fit and carry the brand, pinned by `tests/Feature/PageTitleTest.php`. `Seo::title` also had the same mid-word truncation bug as the description and now cuts on a word boundary.

One is left for you: **blog post titles** still run long (the sample post reaches 68 characters) because the post title itself is CMS content. Each post has its own SEO-title field — use it for posts with long titles.

### Not done, and why

- **The 21 navigation flags** (~490 ms response time from `flagcdn.com`, on every page) — the fix is the sprite pattern already used on the Referral page, but the nav's country list is CMS-managed, so it needs a sprite-regeneration step or a new country shows the wrong flag. Needs a decision, not just code.
- **Longer asset caching.** Assets are served with a 7-day cache. Raising that is **a LiteSpeed server-config change, not a code change** — `public/.htaccess` already asks for a year and LiteSpeed ignores it (there is a note in that file about the same trap). It is also not safe yet: most files under `/assets/` have no version in their URL, so a one-year cache would freeze a replaced image in visitors' browsers. Route them through `App\Support\Asset::v()` first, then raise it.
- **Splitting the stylesheet.** One 441 KB stylesheet (69 KB compressed) is loaded by every page and carries the styles for every *other* page. Worth doing, but it is a real project with real regression risk, and it needs visual checking page by page.
- **The hero image** is ~300 KB on desktop. Phones already get a smaller version. Cutting its quality setting would save ~40% but visibly changes the photo, so it is your call, not a silent change.
- **Full-page HTML caching — do not.** Each page carries a per-visitor security token; caching the HTML would serve one person's token to everyone and break every form on the site.

---

## Part 5 — What Search Console actually shows (22 Sep 2026)

Search Console **is** connected — the domain is verified by DNS, not by the meta tag (see the note under Quick reference). These are the first real numbers.

### The baseline to measure against

| Window | Clicks | Impressions | CTR |
|---|---|---|---|
| Last 3 months | 330 | 6,300 | 5.24% |
| Last 28 days | 67 | 2,271 | 2.95% |
| Last 7 days | 21 | 547 | 3.84% |

| Month | Clicks/day | Impressions/day | CTR |
|---|---|---|---|
| June | 3.64 | 37.5 | 9.71% |
| July | 3.94 | 54.1 | 7.27% |
| August | 3.84 | 84.4 | 4.55% |
| September | 2.58 | 83.8 | 3.08% |

**The brand fight is won.** `one degree advisory` ranks **1.22** with 35% CTR. The San Diego firm is no longer taking your name.

**But 74% of all clicks are people searching your own name** — 244 of 330. Visible non-brand clicks over three months number about seven. Impressions more than doubled while clicks stayed flat, because the new impressions are on things you cannot win: `degree` alone brought 206 impressions and zero clicks, `study in belgium` 195 impressions at position 61.

**The winnable cluster is local.** `education consultancy in jaipur` (position 6.4), `study abroad consultants in jaipur` (11.9), `education consultant in jaipur` (12.4), `study abroad in jaipur` (18.5), `career counselling in jaipur` (22.1). These are commercial, local, and just off the top — and **Google Business Profile is the lever for all of them**. It remains the single highest-value item on this whole page.

For the record: `study abroad` on its own drew 6 impressions in three months. It is not a term you are competing for.

### 🔴 Found on the live site — needs your decision, not a developer's

The MBBS country pages are built by scraping **avglobaloverseas.com**, and the scrape brought that company's *claims* across, not just its words. Live on `/mbbs/country/georgia` right now:

> "**AV Global owns and manages student hostels** near partner universities in Tbilisi. Dedicated Indian student hostels with Indian mess, Wi-Fi, 24/7 security, laundry, and **an AV Global coordinator living on site**. Monthly cost including food: Rs 12,500 to Rs 25,000."

> "Your child will not go hungry for home food — **we made sure of that**."

and every MBBS page carries the badge **"India's Trusted MBBS Abroad Consultants Since 2009"**.

Read as your page, that tells a parent you run hostels in Tbilisi, staff them, charge for them, and have traded since 2009. **We have deliberately not "fixed" this by swapping the name**, because changing "AV Global owns and manages" to "One Degree Advisory owns and manages" would turn someone else's claim into a false one of yours. Three possible answers, and only you know which is true:

1. It is a real partnership → attribute it plainly ("our accommodation partner in Tbilisi operates …").
2. It is not → the hostel, mess and on-site-coordinator passages come out, along with the "Since 2009" badge.
3. You do offer something equivalent → describe what you actually provide.

Until then, these pages are making commitments in your name to families choosing where to send their child.

**What was fixed**: the partner's name is out of every MBBS page *title*. Live, Georgia read `… | NMC Approved Guide | AV Global | One Degree Adv` — a competitor named in your search result and your own name cut in half. All 23 now end in the full "| One Degree Advisory", pinned by a test.

### Country guides rewritten

`/countries/study-in-belgium`, `/countries/study-in-georgia` and `/countries/study-in-kazakhstan` have been rewritten by hand (521 → 796, 510 → 756, 730 → 923 words), with new titles, descriptions and section copy. The scraped copy they replaced was the partner page's DOM text joined with `|`, from which the site could salvage one sentence per section — and the same sentence was then printed twice, once as a paragraph and once as a heading.

The copy lives in `resources/data/country-guides/`, **not** in the CMS, so that running country-sync again cannot overwrite it. Adding a file there rewrites another guide; anything you leave out keeps the scraped value.

### 📅 Re-measure on or after 20 October 2026

Export the same four CSVs (Queries, Pages, Countries, Devices) for **Last 28 days** and compare against the 28-day row above: **67 clicks / 2,271 impressions / 2.95% CTR**.

What should move, and why:
- **CTR on the pages whose descriptions were cut mid-sentence** — twelve pages were over the 160-character limit and were being chopped at 170 *mid-word* before Google ever saw them.
- **Belgium, Georgia and Kazakhstan positions** — from 39.0, 13.5 and 10.7 respectively.
- **The Jaipur cluster**, if Google Business Profile has been set up by then.

Allow 3–4 weeks: Google has to recrawl and re-evaluate before any of it shows.

---

## Quick reference — values your developer still needs from you

| Where | Value | Source |
|---|---|---|
| Live `.env` → `GOOGLE_MAPS_PLACE_URL` | the Maps place URL | Google Business Profile, after verification |
| `config/site.php` → `contact.geo` | exact lat/lng | Google Business Profile pin (current values are approximate) |

**`GOOGLE_SITE_VERIFICATION` is not needed** — checked 21 Sep 2026. It is empty in the live `.env`, and it should stay that way: the domain is already verified with Google by **DNS**, which is the better method and the one recommended above. The TXT record `google-site-verification=5ANNdWdoNEVraynOj4bC0WtIVRHxO7v-ChSovabwB8I` is live on the domain, alongside a Google Workspace record. You only ever need one verification method, so the HTML meta tag is redundant here. (Note the DNS token cannot be pasted into `.env` — Google issues a separate token per method.)

DNS for this domain is **not** at Hostinger: the nameservers are `ns59`/`ns60.domaincontrol.com`, i.e. GoDaddy. That is where any future DNS record has to be added.

---

*This is a living checklist. The code side is complete; ranking is now a function of consistently doing Part 2.*
