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

## Quick reference — values your developer still needs from you

| Where | Value | Source |
|---|---|---|
| Live `.env` → `GOOGLE_SITE_VERIFICATION` | the HTML-tag verification code | Google Search Console (if using meta verification) |
| Live `.env` → `GOOGLE_MAPS_PLACE_URL` | the Maps place URL | Google Business Profile, after verification |
| `config/site.php` → `contact.geo` | exact lat/lng | Google Business Profile pin (current values are approximate) |

---

*This is a living checklist. The code side is complete; ranking is now a function of consistently doing Part 2.*
