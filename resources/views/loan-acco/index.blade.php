{{-- Loan & Acco — Education-Loan + Student-Accommodation landing page under the
     Student Hub. Rendered on the shared site layout (layouts.app), so the
     navbar, footer, contact FAB and the success/fail form popup are identical to
     the rest of the site. The page body is fully scoped under #la-page so its
     navy/orange styling never touches the shared chrome.

     Both enquiry forms are ordinary POST forms tagged data-loan-acco-form; the
     shared script.js (wireFormSubmit) AJAX-submits them and shows the native
     popup with the {title, message} JSON returned by LoanAccoController. --}}
@extends('layouts.app')

@php
    // Country options shared by both enquiry forms.
    $countries = ['Germany','France','Italy','Netherlands','Canada','UK','Australia','New Zealand','USA','Ireland','Poland','Spain','Finland','Belgium','UAE','Georgia','Kazakhstan','Uzbekistan','Russia'];
@endphp

@push('head')
{{-- No font <link> here: the page now uses the site stack, already loaded by the
     shared layout. --}}
<style>
  /* Neutralise the site's body gradient (fades to dark for the footer) so this
     paper-white page has no dark band above the footer. */
  body.la-page-body { background:#fff; }

  /* ============================================================
     Everything below is scoped under #la-page so it can never
     touch the shared navbar / footer (which sit outside it).
     ============================================================ */
  #la-page{
    --navy-deep:#0A0752;
    --navy:#1C0C8C;
    --navy-soft:#EAEBFB;
    --orange:#FF5722;
    --orange-soft:#FFE7DC;
    --orange-tint:#FF8A50;
    --ink:#0A0752;
    --muted:#615E8C;
    --paper:#FFFFFF;
    --line:#DEDFF5;
    --radius:18px;

    /* Site type stack — same as home / About, which use exactly two faces.
       --font-mono is a label/eyebrow role (never code), so it maps to the body
       face: home and About render every eyebrow in Manrope, not a third family.
       Kept as its own variable so the role stays nameable. */
    --font-head:"Cormorant Garamond",Georgia,serif;
    --font-body:"Manrope",system-ui,-apple-system,"Segoe UI",sans-serif;
    --font-mono:"Manrope",system-ui,-apple-system,"Segoe UI",sans-serif;

    font-family:var(--font-body);
    color:var(--ink);
    background:var(--paper);
    line-height:1.55;
    -webkit-font-smoothing:antialiased;
  }
  #la-page *{box-sizing:border-box;}
  {{-- letter-spacing stays at normal: the -0.02em tracking was tuned for Sora,
       and a high-contrast serif does not want negative tracking. --}}
  #la-page h1,#la-page h2,#la-page h3{font-family:var(--font-head); font-weight:700; line-height:1.1; max-width:none;}
  #la-page h1{color:var(--navy);}
  #la-page a{color:inherit; text-decoration:none;}
  #la-page img{display:block; max-width:100%;}
  #la-page p{margin:0;}
  #la-page .wrap{max-width:1160px; margin:0 auto; padding:0 28px;}
  #la-page .eyebrow{
    font-family:var(--font-mono); font-size:14px; font-weight:500;
    letter-spacing:0.14em; text-transform:uppercase; color:var(--orange);
    display:flex; align-items:center; gap:8px; margin-bottom:14px;
  }

  /* ---------- HERO ---------- */
  #la-page .hero{
    position:relative;
    background:radial-gradient(circle at 82% 30%, #F3F1FC 0%, #FFFFFF 55%);
    color:var(--ink); overflow:hidden; padding:80px 0 76px; border-bottom:1px solid var(--line);
  }
  #la-page .hero .ring{position:absolute; border:1.5px solid rgba(43,27,143,0.10); border-radius:50%; top:50%; left:78%; transform:translate(-50%,-50%);}
  #la-page .hero .ring.r1{width:520px; height:520px;}
  #la-page .hero .ring.r2{width:380px; height:380px; border-color:rgba(255,87,34,0.20);}
  #la-page .hero .ring.r3{width:240px; height:240px;}
  #la-page .hero .degree-dot{
    position:absolute; width:14px; height:14px; border-radius:50%; background:var(--orange);
    top:50%; left:78%; animation:la-orbit 22s linear infinite; box-shadow:0 0 24px rgba(255,87,34,0.5);
  }
  @keyframes la-orbit{
    from{transform:translate(-50%,-50%) rotate(0deg) translateX(190px) rotate(0deg);}
    to{transform:translate(-50%,-50%) rotate(360deg) translateX(190px) rotate(-360deg);}
  }
  #la-page .hero-inner{position:relative; z-index:2; max-width:680px;}
  #la-page .hero h1{font-size:clamp(38px,5.4vw,60px); margin-bottom:22px; color:var(--navy);}
  #la-page .hero h1 em{font-style:normal; color:var(--orange);}
  #la-page .hero p.lead{font-size:18px; color:var(--muted); max-width:520px; margin-bottom:34px;}
  #la-page .hero-ctas{display:flex; gap:14px; flex-wrap:wrap; margin-bottom:52px;}
  #la-page .btn{
    display:inline-flex; align-items:center; gap:8px; padding:14px 26px; border-radius:100px;
    font-weight:600; font-size:15px; cursor:pointer; border:none; font-family:var(--font-body);
    transition:transform 0.15s ease, box-shadow 0.15s ease;
  }
  #la-page .btn:hover{transform:translateY(-2px);}
  #la-page .btn-primary{background:var(--orange); color:#fff; box-shadow:0 10px 24px rgba(255,87,34,0.35);}
  #la-page .btn-ghost{background:var(--navy-soft); color:var(--navy); border:1px solid var(--line);}
  #la-page .btn-navy{background:var(--navy); color:#fff; box-shadow:0 10px 24px rgba(43,27,143,0.3);}
  #la-page .btn-block{width:100%; justify-content:center; margin-top:28px;}
  #la-page .btn[disabled]{opacity:.65; cursor:progress; transform:none;}

  #la-page .hero-stats{display:flex; gap:44px; flex-wrap:wrap;}
  #la-page .hero-stats div{border-left:2px solid var(--line); padding-left:14px;}
  #la-page .hero-stats .num{font-family:var(--font-head); font-size:26px; font-weight:700; color:var(--navy);}
  #la-page .hero-stats .lbl{font-size:12.5px; color:var(--muted); text-transform:uppercase; letter-spacing:0.06em; margin-top:2px;}

  /* ---------- SERVICE CARDS ---------- */
  #la-page .services{padding:76px 0 40px;}
  #la-page .services-grid{display:grid; grid-template-columns:1fr 1fr; gap:22px;}
  #la-page .service-card{
    border:1px solid var(--line); border-radius:var(--radius); padding:36px; background:var(--paper);
    transition:box-shadow 0.2s ease, transform 0.2s ease; display:flex; flex-direction:column;
  }
  #la-page .service-card:hover{box-shadow:0 20px 40px rgba(21,15,61,0.08); transform:translateY(-3px);}
  #la-page .service-card .icon{
    width:76px; height:76px; border-radius:16px; display:flex; align-items:center; justify-content:center;
    margin-bottom:22px; border:1.5px solid var(--line);
  }
  #la-page .service-card .icon svg{width:34px; height:34px;}
  #la-page .service-card.loan .icon{color:var(--navy); background:var(--navy-soft);}
  #la-page .service-card.stay .icon{color:var(--orange); background:var(--orange-soft);}
  #la-page .service-card h3{font-size:30px; margin-bottom:10px; color:var(--navy);}
  #la-page .service-card p{color:var(--muted); font-size:15px; margin-bottom:22px; flex-grow:1;}
  #la-page .service-card .go{font-weight:600; font-size:14.5px; display:inline-flex; align-items:center; gap:6px;}
  #la-page .service-card.loan .go{color:var(--navy);}
  #la-page .service-card.stay .go{color:var(--orange);}
  #la-page .card-foot{display:flex; align-items:center; justify-content:space-between; gap:10px; margin-top:auto;}
  #la-page .wa-btn{width:34px; height:34px; border-radius:50%; display:flex; align-items:center; justify-content:center; flex-shrink:0;}
  #la-page .service-card.loan .wa-btn{background:var(--navy-soft); color:var(--navy);}
  #la-page .service-card.stay .wa-btn{background:var(--orange-soft); color:var(--orange);}
  #la-page .wa-btn svg{width:17px; height:17px;}

  /* ---------- SECTION SHELLS ---------- */
  #la-page section.block{padding:84px 0;}
  #la-page section.block.tint{background:var(--navy-soft);}
  #la-page .block-head{max-width:640px; margin-bottom:48px;}
  #la-page .block-head h2{font-size:clamp(28px,3.4vw,38px); margin-bottom:14px; color:var(--navy);}
  #la-page .block-head p{color:var(--muted); font-size:16.5px;}

  /* loan type cards */
  #la-page .type-grid{display:grid; grid-template-columns:1fr 1fr; gap:22px; margin-bottom:60px;}
  #la-page .type-card{background:#fff; border:1px solid var(--line); border-radius:var(--radius); padding:30px;}
  #la-page .type-card .tag{
    display:inline-block; font-family:var(--font-mono); font-size:11.5px; letter-spacing:0.08em;
    text-transform:uppercase; padding:5px 12px; border-radius:100px; margin-bottom:18px;
  }
  #la-page .type-card.collateral .tag{background:var(--navy-soft); color:var(--navy);}
  #la-page .type-card.noncollateral .tag{background:var(--orange-soft); color:var(--orange);}
  #la-page .type-card h4{font-family:var(--font-head); font-size:21px; margin-bottom:16px; color:var(--navy);}
  #la-page .type-card ul{list-style:none; margin:0; padding:0;}
  #la-page .type-card li{font-size:14.5px; color:var(--ink); padding:8px 0; border-top:1px solid var(--line); display:flex; gap:10px; align-items:flex-start;}
  #la-page .type-card li:first-of-type{border-top:none;}
  #la-page .type-card li::before{content:"✓"; color:var(--orange); font-weight:700;}

  /* chips row for accommodation types */
  #la-page .chip-row{display:flex; flex-wrap:wrap; gap:10px; margin-bottom:40px;}
  #la-page .chip{font-size:13.5px; font-weight:500; padding:9px 16px; border-radius:100px; background:#fff; border:1px solid var(--line); color:var(--navy);}
  #la-page .chip.chip-alt{background:var(--orange-soft); border-color:var(--orange-soft); color:var(--orange);}

  /* ---------- FORMS ---------- */
  #la-page .form-shell{background:#fff; border:1px solid var(--line); border-radius:22px; padding:40px; box-shadow:0 30px 60px rgba(21,15,61,0.06);}
  #la-page .form-grid{display:grid; grid-template-columns:1fr 1fr; gap:18px 20px;}
  #la-page .field{display:flex; flex-direction:column; gap:7px;}
  #la-page .field.full{grid-column:1/-1;}
  #la-page label{font-size:12.5px; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:var(--navy);}
  #la-page input, #la-page select{
    font-family:var(--font-body); padding:13px 14px; border-radius:10px; border:1.5px solid var(--line);
    font-size:14.5px; color:var(--ink); background:#FBFAFF; transition:border-color 0.15s ease; width:100%;
  }
  #la-page input:focus, #la-page select:focus{outline:none; border-color:var(--orange); background:#fff;}
  #la-page select{appearance:none; background-image:url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='14' height='9'><path d='M1 1l6 6 6-6' stroke='%236B6584' stroke-width='1.6' fill='none'/></svg>"); background-repeat:no-repeat; background-position:right 14px center;}
  #la-page .radio-group{display:flex; gap:10px; flex-wrap:wrap;}
  #la-page .radio-pill{
    flex:1; min-width:150px; border:1.5px solid var(--line); border-radius:10px; padding:12px 14px;
    display:flex; align-items:center; gap:10px; cursor:pointer; font-size:14px; background:#FBFAFF;
  }
  #la-page .radio-pill input{width:auto; padding:0;}
  #la-page .radio-pill:has(input:checked){border-color:var(--orange); background:var(--orange-soft);}
  #la-page .other-field{display:none; margin-top:10px;}
  #la-page .other-field.show{display:flex;}

  /* ---------- FAQ ---------- */
  #la-page details{border:1px solid var(--line); border-radius:14px; padding:18px 22px;}
  #la-page details summary{cursor:pointer; font-weight:600; color:var(--navy); font-family:var(--font-head);}
  #la-page details p{margin-top:10px; color:var(--muted); font-size:14.5px;}

  /* ---------- CONNECT ---------- */
  #la-page .connect-grid{display:grid; grid-template-columns:1fr 1fr; gap:22px; align-items:stretch;}
  #la-page .connect-list{display:flex; flex-direction:column; gap:14px;}
  #la-page .connect-item{display:flex; align-items:center; gap:16px; background:#fff; border:1px solid var(--line); border-radius:16px; padding:18px 20px; transition:border-color 0.2s ease, transform 0.2s ease;}
  #la-page .connect-item:hover{border-color:var(--orange); transform:translateX(3px);}
  #la-page .connect-item .ci-icon{width:44px; height:44px; border-radius:12px; background:var(--navy-soft); color:var(--navy); display:flex; align-items:center; justify-content:center; flex-shrink:0;}
  #la-page .connect-item .ci-icon svg{width:20px; height:20px;}
  #la-page .connect-item .ci-label{font-family:var(--font-mono); font-size:11px; text-transform:uppercase; letter-spacing:0.06em; color:var(--muted); margin-bottom:3px;}
  #la-page .connect-item .ci-value{font-size:15px; font-weight:600; color:var(--ink);}
  #la-page .connect-map{border-radius:18px; overflow:hidden; border:1px solid var(--line); min-height:320px; height:100%;}
  #la-page .connect-map iframe{width:100%; height:100%; min-height:320px; border:0; display:block;}

  @media (max-width:760px){
    #la-page .services-grid,
    #la-page .type-grid,
    #la-page .connect-grid{grid-template-columns:1fr;}
  }
  @media (max-width:640px){
    #la-page .form-grid{grid-template-columns:1fr;}
    #la-page .form-shell{padding:26px;}
  }
</style>
@endpush

@section('content')
<main id="{{ $mainId ?? 'main' }}">
  <div id="la-page">

    <section class="hero">
      <div class="ring r1"></div>
      <div class="ring r2"></div>
      <div class="ring r3"></div>
      <div class="degree-dot"></div>
      <div class="wrap hero-inner">
        <div class="eyebrow">Education Loans · Student Housing · Worldwide</div>
        <h1>One degree <em>away</em> from everywhere you want to be.</h1>
        <p class="lead">One Degree Advisory finances your international degree and finds the room you'll live in while you earn it — under one roof, one advisor, one timeline.</p>
        <div class="hero-ctas">
          <a href="#loan" class="btn btn-primary">Apply for an Education Loan</a>
          <a href="#accommodation" class="btn btn-ghost">Find Student Accommodation</a>
        </div>
        <div class="hero-stats">
          <div><div class="num">25+</div><div class="lbl">Destinations</div></div>
          <div><div class="num">₹2 Cr</div><div class="lbl">Max loan value</div></div>
          <div><div class="num">2</div><div class="lbl">Loan routes — with / without collateral</div></div>
        </div>
      </div>
    </section>

    <section class="services">
      <div class="wrap services-grid">
        <div class="service-card loan">
          <div class="icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
          </div>
          <h3>Education Loan</h3>
          <p>Collateral and non-collateral routes, matched to your family's financial profile — from application to disbursal.</p>
          <div class="card-foot">
            <a href="#loan" class="go">Apply for an education loan →</a>
            <a href="https://wa.me/918233365888?text=Hi%2C%20I%27d%20like%20to%20talk%20to%20the%20education%20loan%20team." target="_blank" rel="noopener" class="wa-btn" title="Connect with the loan team on WhatsApp">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
            </a>
          </div>
        </div>
        <div class="service-card stay">
          <div class="icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/></svg>
          </div>
          <h3>Accommodation</h3>
          <p>Verified student housing near your university — studios, shared flats, residences and homestays, booked before you land.</p>
          <div class="card-foot">
            <a href="#accommodation" class="go">Find student accommodation →</a>
            <a href="https://wa.me/918233365888?text=Hi%2C%20I%27d%20like%20to%20talk%20to%20the%20accommodation%20team." target="_blank" rel="noopener" class="wa-btn" title="Connect with the accommodation team on WhatsApp">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
            </a>
          </div>
        </div>
      </div>
    </section>

    {{-- ================= LOAN SECTION ================= --}}
    <section class="block" id="loan">
      <div class="wrap">
        <div class="block-head">
          <div class="eyebrow"><svg viewBox="0 0 24 24" style="width:17px;height:17px;flex-shrink:0;" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>Education Loan</div>
          <h2>Fund the degree. Not delay it.</h2>
          <p>Choose the loan route that fits your family's situation and visa requirement, then send us your details — an advisor reviews every application personally within 48-72 hours.</p>
        </div>

        <div class="type-grid">
          <div class="type-card collateral">
            <span class="tag">Collateral Loan</span>
            <h4>Property-backed loan</h4>
            <ul>
              <li>Loan up to ₹2 Crore</li>
              <li>Lower interest rate</li>
              <li>Higher approval chance</li>
              <li>Longer repayment tenure</li>
            </ul>
          </div>
          <div class="type-card noncollateral">
            <span class="tag">Non-Collateral Loan</span>
            <h4>No property required</h4>
            <ul>
              <li>Fast approval</li>
              <li>Available for selected universities</li>
              <li>Quick processing</li>
              <li>Flexible repayment</li>
            </ul>
          </div>
        </div>

        <div class="form-shell">
          <h3 style="font-size:22px; margin-bottom:6px; color:var(--navy);">Apply for an Education Loan</h3>
          <p style="color:var(--muted); font-size:14.5px; margin-bottom:26px;">Takes under 2 minutes. No documents needed yet — this is just to check your eligibility.</p>

          <form id="loanForm" method="POST" action="{{ route('loan-acco.lead') }}" data-loan-acco-form>
            @csrf
            <input type="hidden" name="form" value="loan">
            <div class="form-grid">
              <div class="field">
                <label for="loanName">Full name</label>
                <input type="text" id="loanName" name="name" required placeholder="As per your passport">
              </div>
              <div class="field">
                <label for="loanPhone">Phone number</label>
                <input type="tel" id="loanPhone" name="phone" required placeholder="+91 XXXXX XXXXX">
              </div>
              <div class="field">
                <label for="loanEmail">Email address</label>
                <input type="email" id="loanEmail" name="email" required placeholder="you@email.com">
              </div>
              <div class="field">
                <label for="loanCountry">Country of study</label>
                <select id="loanCountry" name="country" data-la-country>
                  <option value="">Select a country</option>
                  @foreach($countries as $c)<option>{{ $c }}</option>@endforeach
                  <option value="Other">Other — my country isn't listed</option>
                </select>
                <div class="field other-field" data-la-country-other>
                  <input type="text" name="country_other" placeholder="Type your destination country">
                </div>
              </div>
              <div class="field">
                <label for="loanCourse">Course / program</label>
                <input type="text" id="loanCourse" name="course" placeholder="e.g. MS Computer Science">
              </div>
              <div class="field">
                <label for="loanUniversity">University (if known)</label>
                <input type="text" id="loanUniversity" name="university" placeholder="Optional">
              </div>
              <div class="field full">
                <label>Preferred loan type</label>
                <div class="radio-group">
                  <label class="radio-pill"><input type="radio" name="loan_type" value="Collateral" checked> Collateral</label>
                  <label class="radio-pill"><input type="radio" name="loan_type" value="Non-Collateral"> Non-Collateral</label>
                  <label class="radio-pill"><input type="radio" name="loan_type" value="Not sure"> Not sure yet</label>
                </div>
              </div>
              <div class="field">
                <label for="loanAmount">Loan amount required</label>
                <select id="loanAmount" name="loan_amount">
                  <option>Under ₹20 Lakh</option>
                  <option>₹20 – 50 Lakh</option>
                  <option>₹50 Lakh – ₹1 Crore</option>
                  <option>₹1 – 2 Crore</option>
                  <option>Above ₹2 Crore</option>
                </select>
              </div>
              <div class="field">
                <label for="loanCibil">Approx. co-applicant CIBIL score</label>
                <select id="loanCibil" name="cibil">
                  <option>750+ (Excellent)</option>
                  <option>700 – 749 (Good)</option>
                  <option>650 – 699 (Average)</option>
                  <option>Below 650</option>
                  <option>Not sure</option>
                </select>
              </div>
              <div class="field full">
                <label>Is property available for collateral?</label>
                <div class="radio-group">
                  <label class="radio-pill"><input type="radio" name="property" value="Yes" checked> Yes</label>
                  <label class="radio-pill"><input type="radio" name="property" value="No"> No</label>
                  <label class="radio-pill"><input type="radio" name="property" value="Maybe"> Maybe / need advice</label>
                </div>
              </div>
            </div>
            <button type="submit" class="btn btn-navy btn-block"><span>Check My Loan Eligibility</span></button>
          </form>
        </div>
      </div>
    </section>

    {{-- ================= ACCOMMODATION SECTION ================= --}}
    <section class="block tint" id="accommodation">
      <div class="wrap">
        <div class="block-head">
          <div class="eyebrow"><svg viewBox="0 0 24 24" style="width:17px;height:17px;flex-shrink:0;" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/></svg>Accommodation</div>
          <h2>A room booked before you land.</h2>
          <p>Tell us where you're headed and how you like to live — we match you with verified housing partners and handle the booking.</p>
        </div>

        <div class="chip-row">
          <span class="chip">Student Housing</span>
          <span class="chip chip-alt">Shared Apartment</span>
          <span class="chip">Studio</span>
          <span class="chip chip-alt">Utilities &amp; WiFi Setup</span>
        </div>

        <div class="form-shell">
          <h3 style="font-size:22px; margin-bottom:6px; color:var(--navy);">Apply for Accommodation</h3>
          <p style="color:var(--muted); font-size:14.5px; margin-bottom:26px;">Share your destination and preferences — we'll shortlist verified options within your budget.</p>

          <form id="stayForm" method="POST" action="{{ route('loan-acco.lead') }}" data-loan-acco-form>
            @csrf
            <input type="hidden" name="form" value="accommodation">
            <div class="form-grid">
              <div class="field">
                <label for="stayName">Full name</label>
                <input type="text" id="stayName" name="name" required placeholder="As per your passport">
              </div>
              <div class="field">
                <label for="stayPhone">Phone number</label>
                <input type="tel" id="stayPhone" name="phone" required placeholder="+91 XXXXX XXXXX">
              </div>
              <div class="field">
                <label for="stayEmail">Email address</label>
                <input type="email" id="stayEmail" name="email" required placeholder="you@email.com">
              </div>
              <div class="field">
                <label for="stayCountry">Destination country</label>
                <select id="stayCountry" name="country" data-la-country>
                  <option value="">Select a country</option>
                  @foreach($countries as $c)<option>{{ $c }}</option>@endforeach
                  <option value="Other">Other — my country isn't listed</option>
                </select>
                <div class="field other-field" data-la-country-other>
                  <input type="text" name="country_other" placeholder="Type your destination country">
                </div>
              </div>
              <div class="field">
                <label for="stayCity">City / university area</label>
                <input type="text" id="stayCity" name="city" placeholder="e.g. Munich, near TU Munich">
              </div>
              <div class="field">
                <label for="moveDate">Preferred move-in date</label>
                <input type="date" id="moveDate" name="move_date">
              </div>
              <div class="field full">
                <label>Accommodation type</label>
                <div class="radio-group">
                  <label class="radio-pill"><input type="radio" name="stay_type" value="Student Housing" checked> Student Housing</label>
                  <label class="radio-pill"><input type="radio" name="stay_type" value="Shared Apartment"> Shared Apartment</label>
                  <label class="radio-pill"><input type="radio" name="stay_type" value="Studio"> Studio</label>
                </div>
                <div class="radio-group" style="margin-top:10px;">
                  <label class="radio-pill"><input type="radio" name="stay_type" value="University Residence"> University Residence</label>
                  <label class="radio-pill"><input type="radio" name="stay_type" value="Homestay"> Homestay</label>
                  <label class="radio-pill"><input type="radio" name="stay_type" value="Other"> Other</label>
                </div>
                <div class="field other-field" data-la-staytype-other>
                  <input type="text" name="stay_type_other" placeholder="Tell us what kind of stay you're looking for">
                </div>
              </div>
              <div class="field">
                <label for="stayDuration">Duration of stay</label>
                <select id="stayDuration" name="duration">
                  <option>Under 6 months</option>
                  <option>6 – 12 months</option>
                  <option>1 – 2 years</option>
                  <option>2+ years</option>
                </select>
              </div>
              <div class="field">
                <label for="stayBudget">Monthly budget (approx.)</label>
                <select id="stayBudget" name="budget">
                  <option>Under ₹30,000</option>
                  <option>₹30,000 – ₹60,000</option>
                  <option>₹60,000 – ₹1,00,000</option>
                  <option>Above ₹1,00,000</option>
                </select>
              </div>
            </div>
            <button type="submit" class="btn btn-primary btn-block"><span>Find My Accommodation</span></button>
          </form>
        </div>
      </div>
    </section>

    {{-- ================= FAQ ================= --}}
    <section class="block" id="faq">
      <div class="wrap" style="max-width:820px;">
        <div class="block-head">
          <div class="eyebrow">FAQ</div>
          <h2>Common questions</h2>
        </div>
        <div style="display:flex; flex-direction:column; gap:14px;">
          <details>
            <summary>Can I get a loan without collateral?</summary>
            <p>Yes — non-collateral loans are available for selected universities, with approval based on academic profile and co-applicant income rather than property.</p>
          </details>
          <details>
            <summary>How much CIBIL score is required?</summary>
            <p>A co-applicant score of 700+ generally improves approval odds and interest rates, but each lender's threshold differs — our advisors match you to lenders that fit your score.</p>
          </details>
          <details>
            <summary>Can I get accommodation before my visa is approved?</summary>
            <p>In most cases, yes — many partner properties allow conditional booking, refundable if your visa is delayed or declined.</p>
          </details>
          <details>
            <summary>How long does loan approval usually take?</summary>
            <p>Non-collateral loans are typically fastest; collateral loans take a little longer due to property valuation. Your advisor will give you a realistic timeline after reviewing your file.</p>
          </details>
        </div>
      </div>
    </section>

    {{-- ================= CONNECT ================= --}}
    <section class="block" id="contact">
      <div class="wrap">
        <div class="block-head">
          <div class="eyebrow">Connect</div>
          <h2>Talk to the team directly.</h2>
          <p>Reach out and we'll connect you with the right advisor — loans or accommodation.</p>
        </div>
        <div class="connect-grid">
          <div class="connect-list">
            <a class="connect-item" href="mailto:admissions@onedegreeadvisory.com">
              <div class="ci-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 6-10 7L2 6"/></svg></div>
              <div class="ci-text">
                <div class="ci-label">Email</div>
                <div class="ci-value">admissions@onedegreeadvisory.com</div>
              </div>
            </a>
            <a class="connect-item" href="https://wa.me/918233365888" target="_blank" rel="noopener">
              <div class="ci-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg></div>
              <div class="ci-text">
                <div class="ci-label">Call / WhatsApp</div>
                <div class="ci-value">+91 82333 65888</div>
              </div>
            </a>
            <a class="connect-item" href="https://www.google.com/maps/search/?api=1&query=26.8692893,75.7895342" target="_blank" rel="noopener">
              <div class="ci-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/></svg></div>
              <div class="ci-text">
                <div class="ci-label">Office</div>
                <div class="ci-value">A-16A, Van Vihar Colony, Tonk Road, Jaipur, Rajasthan, 302018</div>
              </div>
            </a>
          </div>
          <div class="connect-map">
            <iframe src="https://www.google.com/maps?q=26.8692893,75.7895342&output=embed" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="One Degree Advisory office location"></iframe>
          </div>
        </div>
      </div>
    </section>

  </div>{{-- /#la-page --}}
</main>

<script>
  // Reveal the "Other" free-text fields. Form submission itself is handled by
  // the shared script.js (wireFormSubmit → native success/fail popup).
  (function(){
    document.querySelectorAll('[data-la-country]').forEach(function(sel){
      var wrap = sel.parentElement.querySelector('[data-la-country-other]');
      if (!wrap) return;
      sel.addEventListener('change', function(){
        wrap.classList.toggle('show', sel.value === 'Other');
      });
    });

    document.querySelectorAll('[data-la-staytype-other]').forEach(function(wrap){
      var form = wrap.closest('form');
      if (!form) return;
      form.querySelectorAll('input[name="stay_type"]').forEach(function(radio){
        radio.addEventListener('change', function(){
          wrap.classList.toggle('show', radio.value === 'Other');
        });
      });
    });
  })();
</script>
@endsection
