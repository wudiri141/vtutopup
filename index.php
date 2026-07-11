<?php
// You can change these values anytime
$siteName = "TOPUP";
$navLinks = [
  "Home" => "#home",
  "Features" => "#features",
  "Services" => "#services",
  "Mobile App" => "#mobile-app",
  "Contact us" => "#contact",
];

$apkPath = "download-app.php";

// Stats (you can make them dynamic later)
$stats = [
  ["value" => "72,480",  "label" => "Happy Customers"],
  ["value" => "289,450", "label" => "Transactions Completed"],
  ["value" => "28",      "label" => "Services Offered"],
  ["value" => "58,492",  "label" => "Support Tickets Resolved"],
];

// Live feed for the hero "recharge console" ticker
$liveFeed = [
  ["name" => "Ada O.",   "text" => "topped up ₦1,000 MTN data",        "time" => "Just now"],
  ["name" => "Bello I.", "text" => "paid a DStv subscription",         "time" => "1 min ago"],
  ["name" => "Chika N.", "text" => "recharged ₦500 Airtel airtime",    "time" => "2 mins ago"],
  ["name" => "Femi A.",  "text" => "bought an electricity token",      "time" => "3 mins ago"],
  ["name" => "Grace T.", "text" => "topped up ₦2,000 Glo data",        "time" => "5 mins ago"],
];

// Mobile app "screens" — powers the interactive tab preview
$appScreens = [
  [
    "icon"  => "fa-bolt",
    "title" => "Instant Transactions",
    "desc"  => "Purchase data, airtime, electricity tokens, and cable subscriptions in seconds.",
    "screenLabel" => "Airtime",
    "screenBig"   => "₦500",
    "screenSmall" => "Delivered in 2 seconds",
  ],
  [
    "icon"  => "fa-shield-halved",
    "title" => "Secure Wallet",
    "desc"  => "Fund your wallet safely and keep track of your balance from your phone.",
    "screenLabel" => "Wallet balance",
    "screenBig"   => "₦24,600",
    "screenSmall" => "Protected by bank-level encryption",
  ],
  [
    "icon"  => "fa-bell",
    "title" => "Real-time Notifications",
    "desc"  => "Receive updates for successful purchases, wallet activity, and transaction receipts.",
    "screenLabel" => "New alert",
    "screenBig"   => "Data ✓",
    "screenSmall" => "2GB delivered to 0803•••1234",
  ],
  [
    "icon"  => "fa-clock-rotate-left",
    "title" => "Transaction History",
    "desc"  => "View your past transactions and share or save receipt images whenever you need them.",
    "screenLabel" => "Last transaction",
    "screenBig"   => "GOtv",
    "screenSmall" => "Max — renewed for 1 month",
  ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<meta name="facebook-domain-verification" content="039zl99v4n9gph6vl3hupinbm39zdo" />

<title>VTU TOPUP - Buy Airtime, Data, Cable and Electricity</title>

<meta name="description" content="VTU TOPUP is a fast and secure platform for airtime, data, cable TV and electricity bill payments in Nigeria.">

<meta property="og:title" content="VTU TOPUP">
<meta property="og:description" content="Buy airtime, data, cable TV and electricity easily with VTU TOPUP.">
<meta property="og:site_name" content="VTU TOPUP">
<meta property="og:type" content="website">
<meta property="og:url" content="https://vtutopup.com.ng">

<link rel="icon" type="image/png" sizes="42x42" href="/assets/logo-transparent.png?v=3">
<link rel="icon" type="image/png" sizes="24x24" href="/assets/logo-transparent.png?v=3">
<link rel="apple-touch-icon" sizes="180x180" href="/assets/logo-transparent.png?v=3">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700;800&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "WebSite",
  "name": "VTU TOPUP",
  "alternateName": "VTUTOPUP",
  "url": "https://vtutopup.com.ng/"
}
</script>
<script type="application/ld+json">
{
 "@context": "https://schema.org",
 "@type": "WebSite",
 "name": "VTU TOPUP",
 "url": "https://vtutopup.com.ng/",
 "potentialAction": {
   "@type": "SearchAction",
   "target": "https://vtutopup.com.ng/search?q={search_term_string}",
   "query-input": "required name=search_term_string"
 }
}
</script>
<script type="application/ld+json">
{
 "@context": "https://schema.org",
 "@type": "SiteNavigationElement",
 "name": [
   "Sign In",
   "Create Account",
   "Dashboard",
   "Contact"
 ],
 "url": [
   "https://vtutopup.com.ng/login.php",
   "https://vtutopup.com.ng/register.php",
   "https://vtutopup.com.ng/dashboard.php",
   "https://vtutopup.com.ng/contact.php"
 ]
}
</script>
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Organization",
  "name": "VTU TOPUP",
  "url": "https://vtutopup.com.ng/",
  "logo": "https://vtutopup.com.ng/assets/logo-transparent.png"
}
</script>

<style>
  :root{
    /* ===== ORIGINAL PALETTE — UNCHANGED ===== */
    --bg: #ffffff;
    --text: #0f172a;
    --muted: #475569;
    --line: #e2e8f0;
    --primary: #1d9bf0;
    --primary2: #0ea5e9;
    --soft: #f6f8fb;
    --stats: #169bd5;

    /* derived tokens only — same hues, just alpha/mix, no new colors */
    --primary-rgb: 29,155,240;
    --ink: #020617;
    --radius-lg: 22px;
    --radius-md: 14px;
    --ease: cubic-bezier(.16,1,.3,1);

    /* type system */
    --font-display: 'Space Grotesk', system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
    --font-body: 'Inter', system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
    --font-mono: 'JetBrains Mono', ui-monospace, SFMono-Regular, Menlo, monospace;
  }

  *{ box-sizing: border-box; margin:0; padding:0; }
  html{ scroll-behavior: smooth; }
  body{ background: var(--bg); color: var(--text); font-family: var(--font-body); }
  h1,h2,h3,h4{ font-family: var(--font-display); }
  img{ max-width:100%; }

  @media (prefers-reduced-motion: reduce){
    html{ scroll-behavior:auto; }
    *{ animation-duration:.001ms !important; animation-iteration-count:1 !important; transition-duration:.001ms !important; scroll-behavior:auto !important; }
  }

  /* Container */
  .full-width{ width:100%; padding-left:32px; padding-right:32px; }

  /* reveal-on-scroll */
  .reveal{ opacity:0; transform: translateY(22px); transition: opacity .7s var(--ease), transform .7s var(--ease); }
  .reveal.in-view{ opacity:1; transform:none; }

  /* ============ NAV ============ */
  .nav{
    position: sticky; top:0;
    background: rgba(255,255,255,.86);
    backdrop-filter: blur(12px);
    border-bottom: 1px solid var(--line);
    z-index: 40;
    transition: box-shadow .3s var(--ease), border-color .3s var(--ease);
  }
  .nav.scrolled{ box-shadow: 0 8px 24px rgba(15,23,42,.06); border-bottom-color: transparent; }
  .nav-inner{ display:flex; align-items:center; justify-content:space-between; padding:14px 0; gap:12px; }
  .brand{ display:flex; align-items:center; gap:10px; font-weight:700; letter-spacing:.2px; font-family: var(--font-display); }
  .site-logo{ width:42px; height:42px; object-fit:contain; display:block; }

  .menu{ display:flex; gap:26px; align-items:center; flex-wrap:wrap; }
  .menu a{
    position:relative;
    text-decoration:none; color:var(--text); font-weight:600; font-size:14px; opacity:.85;
    padding: 8px 2px;
  }
  .menu a::after{
    content:""; position:absolute; left:0; right:100%; bottom:2px; height:2px;
    background: var(--primary); transition: right .28s var(--ease);
  }
  .menu a:hover, .menu a.active{ opacity:1; color:var(--primary); }
  .menu a:hover::after, .menu a.active::after{ right:0; }

  .nav-actions{ display:flex; align-items:center; gap:10px; }
  .nav-actions.mobile-shown{ display:none; }
  .btn{
    display:inline-flex; align-items:center; justify-content:center; gap:8px;
    padding:10px 18px; border-radius:999px; font-weight:700; font-size:14px;
    text-decoration:none; border:1px solid var(--line); color:var(--text); background:#fff;
    transition: transform .2s var(--ease), box-shadow .2s var(--ease), background .2s var(--ease);
    cursor:pointer;
  }
  .btn:hover{ transform: translateY(-2px); box-shadow: 0 10px 20px rgba(15,23,42,.08); }
  .btn:active{ transform: translateY(0) scale(.98); }
  .btn-primary{ background: var(--primary); border-color: var(--primary); color:#fff; }
  .btn-primary:hover{ box-shadow: 0 12px 26px rgba(var(--primary-rgb),.35); }
  .btn-outline{ border-color:#b8ddf6; color:var(--primary); background:#fff; }

  .nav-toggle{
    display:none; width:42px; height:42px; border-radius:10px; border:1px solid var(--line);
    background:#fff; align-items:center; justify-content:center; cursor:pointer; flex-direction:column; gap:4px;
  }
  .nav-toggle span{ width:18px; height:2px; background:var(--text); transition: transform .25s var(--ease), opacity .25s var(--ease); }
  .nav-toggle.open span:nth-child(1){ transform: translateY(6px) rotate(45deg); }
  .nav-toggle.open span:nth-child(2){ opacity:0; }
  .nav-toggle.open span:nth-child(3){ transform: translateY(-6px) rotate(-45deg); }

  /* ============ HERO ============ */
  .hero{
    background: radial-gradient(1200px 400px at 20% 10%, #e8f5ff, transparent 60%),
                radial-gradient(900px 400px at 80% 30%, #f0e9ff, transparent 55%),
                var(--soft);
    padding: 56px 0 0;
    overflow: hidden;
  }
  .hero-grid{ display:grid; grid-template-columns: 1.05fr 1fr; gap:40px; align-items:center; padding-bottom:30px; }

  .eyebrow{
    display:inline-flex; align-items:center; gap:8px; font-family: var(--font-mono);
    font-size:12px; font-weight:700; letter-spacing:.06em; text-transform:uppercase;
    color: var(--primary); background: rgba(var(--primary-rgb),.08);
    border:1px solid rgba(var(--primary-rgb),.2); padding:6px 12px; border-radius:999px; margin-bottom:18px;
  }
  .eyebrow .pulse{ width:7px; height:7px; border-radius:50%; background: var(--primary); animation: pulse 1.8s ease-in-out infinite; }
  @keyframes pulse{ 0%,100%{ box-shadow:0 0 0 0 rgba(var(--primary-rgb),.5);} 50%{ box-shadow:0 0 0 6px rgba(var(--primary-rgb),0);} }

  .hero h1{
    font-size: clamp(34px, 4.2vw, 58px);
    line-height: 1.02; letter-spacing:-1px; margin-bottom:16px; font-weight:700;
  }
  .hero h1 .accent{ color: var(--primary); }
  .hero p.lead{ color:var(--muted); font-size:16px; line-height:1.65; max-width:520px; margin-bottom:22px; }
  .hero-actions{ display:flex; gap:12px; flex-wrap:wrap; margin-top:6px; margin-bottom:30px; }

  .trust-row{ display:flex; align-items:center; gap:14px; color:var(--muted); font-size:13px; }
  .trust-row .avatars{ display:flex; }
  .trust-row .avatars span{
    width:30px; height:30px; border-radius:50%; border:2px solid #fff; margin-left:-8px;
    background: linear-gradient(135deg, var(--primary), var(--primary2)); display:inline-block;
  }
  .trust-row .avatars span:first-child{ margin-left:0; }

  /* ===== Recharge console — signature interactive element ===== */
  .recharge-console{
    background:#fff; border:1px solid var(--line); border-radius: var(--radius-lg);
    box-shadow: 0 20px 50px rgba(15,23,42,.10);
    max-width: 480px; margin-left:auto; overflow:hidden;
    animation: rise .8s var(--ease) both;
  }
  @keyframes rise{ from{ opacity:0; transform: translateY(24px) scale(.98);} to{ opacity:1; transform:none; } }

  .console-head{
    display:flex; align-items:center; gap:8px; padding:14px 18px;
    border-bottom:1px solid var(--line); background: var(--soft);
  }
  .console-dot{ width:9px; height:9px; border-radius:50%; background: var(--line); }
  .console-title{
    margin-left:auto; font-family: var(--font-mono); font-size:11px; font-weight:700;
    letter-spacing:.06em; text-transform:uppercase; color:var(--muted);
  }

  .console-body{ padding:22px 20px 8px; }
  .console-label{ display:block; font-size:12px; font-weight:700; color:var(--muted); margin-bottom:10px; }
  .console-label:not(:first-child){ margin-top:18px; }

  .network-row, .amount-row{ display:flex; gap:8px; flex-wrap:wrap; }
  .network-chip, .amount-chip{
    font-family: var(--font-mono); font-size:13px; font-weight:700;
    border:1.5px solid var(--line); background:#fff; color:var(--text);
    padding:9px 14px; border-radius:10px; cursor:pointer; transition: all .18s var(--ease);
  }
  .network-chip:hover, .amount-chip:hover{ border-color: var(--primary); }
  .network-chip.active, .amount-chip.active{
    background: var(--text); border-color: var(--text); color:#fff;
  }

  .console-cta{
    width:100%; margin-top:22px; position:relative; overflow:hidden; padding:14px 18px; font-size:15px;
  }
  .console-cta .cta-progress{
    position:absolute; inset:0; left:auto; width:0%; background: rgba(255,255,255,.28);
    transition: width 1.1s linear;
  }
  .console-cta.running .cta-progress{ width:100%; }
  .console-cta .cta-label{ position:relative; z-index:1; }

  .console-status{
    margin-top:14px; font-size:13px; color: var(--muted); min-height:18px;
    display:flex; align-items:center; gap:8px;
  }
  .console-status.success{ color:#0f8b3d; font-weight:600; }
  .console-status .check{ display:none; }
  .console-status.success .check{ display:inline-flex; }

  .console-ticker{
    border-top:1px solid var(--line); margin-top:18px; padding: 6px 0;
    height: 108px; overflow:hidden; position:relative;
    -webkit-mask-image: linear-gradient(180deg, transparent, #000 12%, #000 88%, transparent);
            mask-image: linear-gradient(180deg, transparent, #000 12%, #000 88%, transparent);
  }
  .ticker-track{ display:flex; flex-direction:column; animation: ticker 14s linear infinite; }
  .console-ticker:hover .ticker-track{ animation-play-state: paused; }
  @keyframes ticker{ from{ transform: translateY(0); } to{ transform: translateY(-50%); } }
  .ticker-item{
    display:flex; align-items:center; gap:10px; padding:8px 20px; font-size:12.5px; color:var(--muted);
  }
  .ticker-item strong{ color:var(--text); font-weight:600; }
  .ticker-item .dot{ width:6px; height:6px; border-radius:50%; background:#22c55e; flex:none; }
  .ticker-item .time{ margin-left:auto; font-family: var(--font-mono); font-size:11px; color:#94a3b8; }

  @media (max-width: 900px){
    .hero-grid{ grid-template-columns: 1fr; }
    .recharge-console{ margin: 0; max-width:100%; }
    .menu{ position:fixed; top:71px; left:0; right:0; background:#fff; border-bottom:1px solid var(--line);
      flex-direction:column; align-items:flex-start; padding:14px 24px 20px; gap:4px;
      transform: translateY(-8px); opacity:0; pointer-events:none; transition: all .25s var(--ease);
      box-shadow: 0 16px 30px rgba(15,23,42,.08);
    }
    .menu.open{ transform:none; opacity:1; pointer-events:auto; }
    .menu a{ width:100%; padding:10px 0; }
    .nav-actions{ display:none; }
    .nav-actions.mobile-shown{ display:flex; width:100%; padding-top:8px; margin-top:8px; border-top:1px solid var(--line); }
    .nav-toggle{ display:flex; }
  }

  /* ============ STATS ============ */
  .stats-wrap{
    background: var(--stats); color:#fff; border-top-left-radius:22px; border-top-right-radius:22px;
    padding: 40px 0; margin-top: 22px; position:relative; overflow:hidden;
  }
  .stats-wrap::before{
    content:""; position:absolute; inset:0;
    background: repeating-linear-gradient(115deg, rgba(255,255,255,.05) 0 2px, transparent 2px 46px);
    pointer-events:none;
  }
  .stats{ display:grid; grid-template-columns: repeat(4,1fr); gap:22px; text-align:center; position:relative; }
  .stat .value{ font-family: var(--font-mono); font-size: clamp(28px,3vw,44px); font-weight:700; letter-spacing:.2px; }
  .stat .label{ opacity:.95; margin-top:6px; font-size:13px; font-weight:600; }

  @media (max-width: 900px){ .stats{ grid-template-columns: repeat(2,1fr); } }
  @media (max-width: 520px){ .stats{ grid-template-columns: 1fr; } }

  /* ===== WHY CHOOSE + SERVICES ===== */
  .section{ width:100%; padding:84px 32px; background:#f8fafc; }
  .section.white{ background:#ffffff; }

  .section-head{ text-align:center; max-width:650px; margin:0 auto 50px; }
  .eyebrow-mini{
    font-family: var(--font-mono); font-size:12px; font-weight:700; letter-spacing:.08em;
    text-transform:uppercase; color: var(--primary); display:block; margin-bottom:10px;
  }
  .section h2{ font-size:34px; font-weight:700; margin-bottom:10px; }
  .section h2 span{ color: var(--primary); }
  .section p.sub{ color: var(--muted); font-size:15px; }

  .cards{ display:grid; grid-template-columns:repeat(3,1fr); gap:24px; }
  .card{
    background:#ffffff; border-radius:18px; padding:30px 24px; text-align:left;
    border:1px solid #e5e7eb; transition: transform .35s var(--ease), box-shadow .35s var(--ease), border-color .35s var(--ease);
    transform-style: preserve-3d; will-change: transform;
  }
  .card:hover{ box-shadow: 0 20px 40px rgba(15,23,42,.10); border-color: rgba(var(--primary-rgb),.35); }

  .icon{
    width:52px; height:52px; border-radius:14px; background:#e8f5ff; display:flex; align-items:center;
    justify-content:center; margin-bottom:18px; font-size:22px; color:var(--primary); font-weight:700;
    transition: transform .35s var(--ease), background .35s var(--ease);
  }
  .card:hover .icon{ transform: scale(1.08) rotate(-6deg); background: var(--primary); color:#fff; }

  .card h4{ font-size:16px; font-weight:700; margin-bottom:10px; }
  .card p{ font-size:14px; color:var(--muted); line-height:1.6; }

  @media(max-width:900px){ .cards{ grid-template-columns:repeat(2,1fr); } }
  @media(max-width:520px){ .cards{ grid-template-columns:1fr; } .section{ padding:64px 20px; } }

  /* ===== MOBILE APP DOWNLOAD ===== */
  .app-download{ background:#ffffff; padding:80px 32px 84px; }
  .app-download h2{ text-align:center; font-size:clamp(32px,4vw,52px); line-height:1.05; font-weight:700; color:var(--ink); margin-bottom:16px; }
  .app-download h2 span{ color: var(--primary); }
  .app-download .app-sub{ max-width:760px; margin:0 auto 46px; text-align:center; color:var(--muted); font-size:17px; line-height:1.5; }

  .app-download-grid{ max-width:1140px; margin:0 auto; display:grid; grid-template-columns:1fr .95fr; gap:46px; align-items:center; }

  .app-feature{
    display:grid; grid-template-columns:64px 1fr; gap:18px; align-items:center; margin-bottom:12px;
    padding:14px; border-radius:16px; border:1px solid transparent; cursor:pointer; text-align:left;
    background:none; width:100%; transition: background .25s var(--ease), border-color .25s var(--ease);
  }
  .app-feature:hover{ background: var(--soft); }
  .app-feature.active{ background: #eaf6ff; border-color: rgba(var(--primary-rgb),.3); }

  .app-feature-icon{
    width:64px; height:64px; border-radius:12px; background:#dff4ff; display:flex; align-items:center;
    justify-content:center; color:var(--ink); font-size:24px; transition: background .25s var(--ease), color .25s var(--ease);
  }
  .app-feature.active .app-feature-icon{ background: var(--primary); color:#fff; }

  .app-feature h3{ font-size:19px; line-height:1.2; margin-bottom:4px; color:var(--ink); font-weight:700; }
  .app-feature p{ color: var(--muted); font-size:14px; line-height:1.4; }

  .phone-preview{
    min-height:380px; border-radius:20px; overflow:hidden;
    background:
      radial-gradient(circle at 24% 18%, rgba(255,255,255,.96) 0 6%, transparent 7%),
      linear-gradient(135deg, #dff4ff 0%, #b9e7fb 42%, #1d9bf0 100%);
    display:flex; align-items:center; justify-content:center;
    box-shadow: 0 18px 40px rgba(15,23,42,.16);
  }
  .phone-frame{
    width:min(240px,64%); aspect-ratio:9/18; border-radius:34px; background:#111827; padding:12px;
    box-shadow: 0 24px 40px rgba(15,23,42,.28); transform: rotate(-6deg); transition: transform .4s var(--ease);
  }
  .phone-preview:hover .phone-frame{ transform: rotate(0deg); }
  .phone-screen{
    height:100%; border-radius:26px; background: linear-gradient(180deg,#e8f6fd 0%,#ffffff 100%);
    display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center; padding:22px;
  }
  .phone-screen .screen-icon{
    width:56px; height:56px; border-radius:16px; background: var(--primary); color:#fff; display:flex;
    align-items:center; justify-content:center; font-size:22px; margin-bottom:16px; transition: transform .3s var(--ease);
  }
  .phone-screen .screen-label{ font-size:11px; text-transform:uppercase; letter-spacing:.08em; color:var(--muted); font-weight:700; }
  .phone-screen .screen-big{ font-family: var(--font-mono); font-size:26px; font-weight:700; color:var(--ink); margin:6px 0; }
  .phone-screen .screen-small{ font-size:12px; color:var(--muted); }

  .store-buttons{ max-width:1140px; margin:38px auto 0; display:flex; gap:20px; flex-wrap:wrap; }
  .store-btn{
    min-width:212px; min-height:60px; border-radius:12px; background:#2f2f3b; color:#fff; text-decoration:none;
    display:inline-flex; align-items:center; gap:12px; padding:10px 18px; transition: transform .2s var(--ease), box-shadow .2s var(--ease);
  }
  .store-btn:hover{ transform: translateY(-3px); box-shadow: 0 14px 24px rgba(15,23,42,.2); }
  .store-btn i{ font-size:26px; }
  .store-btn small{ display:block; color:#cbd5e1; font-size:10px; line-height:1; }
  .store-btn strong{ display:block; color:#fff; font-size:15px; line-height:1.15; }

  @media(max-width:900px){ .app-download-grid{ grid-template-columns:1fr; } .phone-preview{ min-height:300px; } }
  @media(max-width:560px){
    .app-download{ padding:56px 20px 60px; }
    .app-download .app-sub{ font-size:15px; }
    .store-buttons{ gap:14px; }
    .store-btn{ width:100%; justify-content:center; }
  }

  /* CTA + FOOTER (scoped dark theme — same values as original) */
  .halo-cta-footer{
    --card: #2f2f3b; --text: #e9eef7; --muted: rgba(233,238,247,.75);
    --line: rgba(255,255,255,.10); --primary: #1db2ff;
    background: #f4f6fb; padding: 76px 0 0;
  }

  .halo-cta-wrap{ width:100%; padding:0 18px 60px; display:flex; justify-content:center; }
  .halo-cta{
    width:min(980px,100%); background: var(--card); border-radius:28px; padding:56px 30px; text-align:center;
    color:var(--text); box-shadow: 0 22px 50px rgba(0,0,0,.2); position:relative; overflow:hidden;
  }
  .halo-cta::before{
    content:""; position:absolute; width:420px; height:420px; border-radius:50%;
    background: radial-gradient(circle, rgba(29,178,255,.35), transparent 70%);
    top:-180px; right:-140px; pointer-events:none;
  }
  .halo-cta h2{ margin-bottom:14px; font-size:clamp(24px,3vw,40px); font-weight:700; position:relative; }
  .halo-cta p{ max-width:720px; margin:0 auto 26px; color:var(--muted); font-size:15px; line-height:1.6; position:relative; }
  .halo-cta-actions{ display:flex; gap:14px; justify-content:center; flex-wrap:wrap; position:relative; }
  .halo-cta .btn-primary{ background:#fff; color:#0f172a; border-color:#fff; }
  .halo-cta .btn-outline{ background:transparent; border:2px solid rgba(255,255,255,.35); color:#fff; }

  .halo-footer{ background:#0f172a; color:#cbd5e1; padding:70px 20px 0; font-size:14px; }
  .vtu-footer-inner{ max-width:1200px; margin:auto; display:grid; grid-template-columns:repeat(4,1fr); gap:40px; }
  .footer-col.brand p{ margin:15px 0; line-height:1.7; }
  .brand-logo{ width:140px; margin-bottom:10px; }
  .footer-col h4{ color:#fff; font-size:16px; margin-bottom:18px; position:relative; font-family: var(--font-display); }
  .footer-col h4::after{ content:""; width:40px; height:2px; background:#38bdf8; display:block; margin-top:6px; }
  .footer-links, .contact-list{ list-style:none; padding:0; }
  .footer-links li, .contact-list li{ margin-bottom:12px; }
  .footer-links a{ color:#cbd5e1; text-decoration:none; transition:.25s; }
  .footer-links a:hover{ color:#38bdf8; padding-left:4px; }
  .contact-list i{ margin-right:8px; color:#38bdf8; }
  .socials{ display:flex; gap:12px; margin-top:15px; }
  .socials a{
    width:36px; height:36px; background:#1e293b; display:flex; align-items:center; justify-content:center;
    border-radius:50%; color:#cbd5e1; transition:.25s;
  }
  .socials a:hover{ background:#38bdf8; color:#020617; transform: translateY(-3px); }
  .halo-footer-bottom{ margin-top:60px; border-top:1px solid #1e293b; text-align:center; padding:20px; font-size:13px; color:#94a3b8; }

  @media (max-width:900px){ .vtu-footer-inner{ grid-template-columns:repeat(2,1fr); } }
  @media (max-width:500px){ .vtu-footer-inner{ grid-template-columns:1fr; } }

  /* back to top */
  .to-top{
    position:fixed; right:22px; bottom:22px; width:46px; height:46px; border-radius:50%;
    background: var(--primary); color:#fff; border:none; display:flex; align-items:center; justify-content:center;
    box-shadow: 0 12px 24px rgba(var(--primary-rgb),.35); cursor:pointer; z-index:30;
    opacity:0; pointer-events:none; transform: translateY(12px); transition: all .3s var(--ease);
  }
  .to-top.show{ opacity:1; pointer-events:auto; transform:none; }
</style>
</head>

<body>

  <!-- NAV -->
  <div class="nav" id="siteNav">
    <div class="full-width">
      <div class="nav-inner">
        <div class="brand">
          <img src="assets/logo-transparent.png" alt="VTU TOPUP" class="site-logo">
          <div>VTU TOPUP</div>
        </div>

        <div class="menu" id="siteMenu">
          <?php foreach($navLinks as $name => $href): ?>
            <a href="<?php echo $href; ?>" data-link="<?php echo $href; ?>"><?php echo $name; ?></a>
          <?php endforeach; ?>

          <div class="nav-actions mobile-shown">
            <a class="btn btn-outline" href="login.php">Login</a>
            <a class="btn btn-primary" href="register.php">Sign Up</a>
          </div>
        </div>

        <div class="nav-actions">
          <a class="btn btn-outline" href="login.php">Login</a>
          <a class="btn btn-primary" href="register.php">Sign Up</a>
        </div>

        <button class="nav-toggle" id="navToggle" aria-label="Toggle menu" aria-expanded="false">
          <span></span><span></span><span></span>
        </button>
      </div>
    </div>
  </div>

  <!-- HERO -->
  <section class="hero" id="home">
    <div class="full-width">
      <div class="hero-grid">

        <div>
          <span class="eyebrow"><span class="pulse"></span> Recharges land in seconds, not minutes</span>
          <h1>
            Instant <br/>
            <span class="accent">VTU</span> Services for <br/>
            Seamless <br/>
            Connectivity
          </h1>

          <p class="lead">
            VTU TOPUP provides lightning-fast data top-ups, airtime recharge, bill payments,
            and cable TV subscriptions at unbeatable rates. Experience the future of digital
            transactions in <?php echo date("Y"); ?>.
          </p>

          <div class="hero-actions">
            <a href="register.php" class="btn btn-primary">Get Started Now</a>
            <a href="login.php" class="btn">Sign In</a>
          </div>

          <div class="trust-row">
            <span class="avatars"><span></span><span></span><span></span><span></span></span>
            Trusted by 72,000+ customers across Nigeria
          </div>
        </div>

        <!-- Interactive recharge console — try-before-you-sign-up demo -->
        <div class="recharge-console" id="rechargeConsole">
          <div class="console-head">
            <span class="console-dot"></span><span class="console-dot"></span><span class="console-dot"></span>
            <span class="console-title">Live demo</span>
          </div>

          <div class="console-body">
            <label class="console-label">Choose network</label>
            <div class="network-row" id="networkRow">
              <button class="network-chip active" data-network="MTN">MTN</button>
              <button class="network-chip" data-network="Glo">Glo</button>
              <button class="network-chip" data-network="Airtel">Airtel</button>
              <button class="network-chip" data-network="9mobile">9mobile</button>
            </div>

            <label class="console-label">Select amount</label>
            <div class="amount-row" id="amountRow">
              <button class="amount-chip" data-amount="100">₦100</button>
              <button class="amount-chip active" data-amount="500">₦500</button>
              <button class="amount-chip" data-amount="1000">₦1,000</button>
              <button class="amount-chip" data-amount="2000">₦2,000</button>
            </div>

            <button class="btn btn-primary console-cta" id="consoleCta" type="button">
              <span class="cta-label">Recharge Now</span>
              <span class="cta-progress"></span>
            </button>

            <div class="console-status" id="consoleStatus" aria-live="polite">
              Try it — tap Recharge Now to see how fast we are.
            </div>
          </div>

          <div class="console-ticker" aria-hidden="true">
            <div class="ticker-track" id="tickerTrack">
              <?php
                // render twice for a seamless CSS loop
                for ($r = 0; $r < 2; $r++):
                  foreach ($liveFeed as $item): ?>
                <div class="ticker-item">
                  <span class="dot"></span>
                  <span><strong><?php echo htmlspecialchars($item['name']); ?></strong> <?php echo htmlspecialchars($item['text']); ?></span>
                  <span class="time"><?php echo htmlspecialchars($item['time']); ?></span>
                </div>
              <?php endforeach; endfor; ?>
            </div>
          </div>
        </div>

      </div>
    </div>

    <!-- STATS -->
    <div class="stats-wrap">
      <div class="full-width">
        <div class="stats">
          <?php foreach($stats as $s):
            $target = (int) str_replace(',', '', $s["value"]);
          ?>
            <div class="stat">
              <div class="value" data-target="<?php echo $target; ?>">0</div>
              <div class="label"><?php echo $s["label"]; ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

  </section>

  <section class="section white reveal" id="features">
    <div class="section-head">
      <span class="eyebrow-mini">Why us</span>
      <h2>Why Choose <span>VTU TOPUP</span></h2>
      <p class="sub">We're revolutionizing the VTU industry with cutting-edge technology and unparalleled customer service.</p>
    </div>

    <div class="cards">
      <div class="card reveal">
        <div class="icon"><i class="fa-solid fa-headset"></i></div>
        <h4>24/7 Support</h4>
        <p>Our dedicated support team is available round the clock via chat, phone, and email to resolve any issues.</p>
      </div>

      <div class="card reveal">
        <div class="icon"><i class="fa-solid fa-mobile-screen"></i></div>
        <h4>User-Friendly App</h4>
        <p>Manage all your transactions seamlessly with our intuitive mobile app available on iOS and Android.</p>
      </div>

      <div class="card reveal">
        <div class="icon"><i class="fa-solid fa-gift"></i></div>
        <h4>Bonus Rewards</h4>
        <p>Earn points on every transaction and redeem them for free data, airtime, and other exciting rewards.</p>
      </div>

      <div class="card reveal">
        <div class="icon"><i class="fa-solid fa-bolt"></i></div>
        <h4>Lightning Fast</h4>
        <p>Instant processing of airtime, data, and bill payments with zero delays.</p>
      </div>

      <div class="card reveal">
        <div class="icon"><i class="fa-solid fa-lock"></i></div>
        <h4>Bank-Level Security</h4>
        <p>Your transactions are protected with military-grade encryption and secure payment gateways for peace of mind.</p>
      </div>

      <div class="card reveal">
        <div class="icon"><i class="fa-solid fa-sack-dollar"></i></div>
        <h4>Best Rates</h4>
        <p>We offer the most competitive rates in the market with exclusive discounts for students and regular customers.</p>
      </div>
    </div>
  </section>

  <section class="section reveal" id="services">
    <div class="section-head">
      <span class="eyebrow-mini">What we offer</span>
      <h2>Our <span>VTU Services</span></h2>
      <p class="sub">Comprehensive range of digital services to meet all your connectivity and payment needs.</p>
    </div>

    <div class="cards">
      <div class="card reveal">
        <div class="icon"><i class="fa-solid fa-wifi"></i></div>
        <h4>Data Bundles</h4>
        <p>Instant data subscription across all Nigerian networks at affordable prices.</p>
      </div>

      <div class="card reveal">
        <div class="icon"><i class="fa-solid fa-phone"></i></div>
        <h4>Airtime Top-Up</h4>
        <p>Recharge airtime instantly for all networks with real-time delivery.</p>
      </div>

      <div class="card reveal">
        <div class="icon"><i class="fa-solid fa-tv"></i></div>
        <h4>Cable TV Subscription</h4>
        <p>DStv, GOtv, and Startimes payments without delays.</p>
      </div>

      <div class="card reveal">
        <div class="icon"><i class="fa-solid fa-lightbulb"></i></div>
        <h4>Utility Bills</h4>
        <p>Electricity bill payments with instant token generation.</p>
      </div>

      <div class="card reveal">
        <div class="icon"><i class="fa-solid fa-graduation-cap"></i></div>
        <h4>Exam PINs</h4>
        <p>WAEC, NECO, NABTEB, and JAMB result-checker or exam PINs available instantly.</p>
      </div>

      <div class="card reveal">
        <div class="icon"><i class="fa-solid fa-right-left"></i></div>
        <h4>Fund Transfers</h4>
        <p>Send money to any bank account or mobile wallet instantly with minimal transaction fees.</p>
      </div>
    </div>
  </section>

  <section class="app-download reveal" id="mobile-app">
    <h2>Download Our <span>Mobile App</span></h2>
    <p class="app-sub">
      Get the VTU TOPUP app for seamless transactions on the go. Tap a feature below to see it live on screen.
    </p>

    <div class="app-download-grid">
      <div id="appFeatureList">
        <?php foreach ($appScreens as $i => $s): ?>
          <button type="button" class="app-feature<?php echo $i === 0 ? ' active' : ''; ?>"
            data-index="<?php echo $i; ?>"
            data-icon="<?php echo htmlspecialchars($s['icon']); ?>"
            data-label="<?php echo htmlspecialchars($s['screenLabel']); ?>"
            data-big="<?php echo htmlspecialchars($s['screenBig']); ?>"
            data-small="<?php echo htmlspecialchars($s['screenSmall']); ?>">
            <div class="app-feature-icon"><i class="fa-solid <?php echo htmlspecialchars($s['icon']); ?>"></i></div>
            <div>
              <h3><?php echo htmlspecialchars($s['title']); ?></h3>
              <p><?php echo htmlspecialchars($s['desc']); ?></p>
            </div>
          </button>
        <?php endforeach; ?>
      </div>

      <div class="phone-preview" aria-label="VTU TOPUP mobile app preview">
        <div class="phone-frame">
          <div class="phone-screen" id="phoneScreen">
            <div class="screen-icon" id="screenIcon"><i class="fa-solid <?php echo htmlspecialchars($appScreens[0]['icon']); ?>"></i></div>
            <span class="screen-label" id="screenLabel"><?php echo htmlspecialchars($appScreens[0]['screenLabel']); ?></span>
            <div class="screen-big" id="screenBig"><?php echo htmlspecialchars($appScreens[0]['screenBig']); ?></div>
            <span class="screen-small" id="screenSmall"><?php echo htmlspecialchars($appScreens[0]['screenSmall']); ?></span>
          </div>
        </div>
      </div>
    </div>

    <div class="store-buttons">
      <a class="store-btn" href="<?php echo $apkPath; ?>" download>
        <i class="fa-brands fa-android"></i>
        <span><small>Download APK for</small><strong>Android</strong></span>
      </a>
      <a class="store-btn" href="#mobile-app" aria-disabled="true">
        <i class="fa-brands fa-apple"></i>
        <span><small>Coming soon on</small><strong>App Store</strong></span>
      </a>
    </div>
  </section>

  <section class="halo-cta-footer" id="contact">
    <div class="halo-cta-wrap reveal">
      <div class="halo-cta">
        <h2>Ready to Experience Seamless VTU<br/>Services?</h2>
        <p>
          Join over 50,000 satisfied customers who trust VTU TOPUP for their daily
          digital transactions. Sign up now and get ₦500 bonus on your first transaction!
        </p>

        <div class="halo-cta-actions">
          <a href="register.php" class="btn btn-primary">Create Free Account</a>
          <a href="login.php" class="btn btn-outline">Sign In</a>
        </div>
      </div>
    </div>

    <footer class="halo-footer">
      <div class="vtu-footer-inner">

        <div class="footer-col brand">
          <img src="assets/logo-transparent.png" alt="VTU TOPUP" class="brand-logo" />
          <p>Providing fast, reliable, and affordable VTU services to individuals and businesses across Nigeria since 2018.</p>
          <div class="socials">
            <a href="https://facebook.com/profile.php?id=61582018853053"><i class="fa-brands fa-facebook-f"></i></a>
            <a href="https://x.com/vtutopup"><i class="fa-brands fa-x-twitter"></i></a>
            <a href="https://wa.me/2349161044495"><i class="fa-brands fa-whatsapp"></i></a>
            <a href="https://www.instagram.com/vtutopup"><i class="fa-brands fa-instagram"></i></a>
            <a href="#"><i class="fa-brands fa-linkedin-in"></i></a>
          </div>
        </div>

        <div class="footer-col">
          <h4>Quick Links</h4>
          <ul class="footer-links">
            <li><a href="#home">Home</a></li>
            <li><a href="#features">Features</a></li>
            <li><a href="#services">Services</a></li>
            <li><a href="#mobile-app">Download App</a></li>
            <li><a href="#contact">FAQ</a></li>
          </ul>
        </div>

        <div class="footer-col">
          <h4>Services</h4>
          <ul class="footer-links">
            <li><a href="#services">Data Bundles</a></li>
            <li><a href="#services">Airtime Recharge</a></li>
            <li><a href="#services">Bill Payments</a></li>
            <li><a href="#services">Cable TV</a></li>
            <li><a href="#services">Exam PINs</a></li>
          </ul>
        </div>

        <div class="footer-col">
          <h4>Contact Info</h4>
          <ul class="contact-list">
            <li><i class="fa-solid fa-location-dot"></i> Nigeria</li>
            <li><i class="fa-solid fa-phone"></i> +234 916 104 4495</li>
            <li><i class="fa-solid fa-envelope"></i> support@vtutopup.com.ng</li>
            <li><i class="fa-solid fa-headset"></i> 24/7 Support</li>
          </ul>
        </div>

      </div>

      <div class="halo-footer-bottom">
        <p>© <span id="yr"></span> VTU TOPUP. All rights reserved.</p>
      </div>
    </footer>
  </section>

  <button class="to-top" id="toTop" aria-label="Back to top" type="button">
    <i class="fa-solid fa-arrow-up"></i>
  </button>

<script>
document.getElementById("yr").textContent = new Date().getFullYear();

/* ---------- Nav: scroll shadow + mobile toggle + scroll-spy ---------- */
const nav = document.getElementById("siteNav");
const toTop = document.getElementById("toTop");
window.addEventListener("scroll", () => {
  const y = window.scrollY;
  nav.classList.toggle("scrolled", y > 8);
  toTop.classList.toggle("show", y > 500);
}, { passive: true });

const navToggle = document.getElementById("navToggle");
const siteMenu = document.getElementById("siteMenu");
navToggle.addEventListener("click", () => {
  const open = siteMenu.classList.toggle("open");
  navToggle.classList.toggle("open", open);
  navToggle.setAttribute("aria-expanded", open ? "true" : "false");
});
siteMenu.querySelectorAll("a").forEach(a => a.addEventListener("click", () => {
  siteMenu.classList.remove("open");
  navToggle.classList.remove("open");
}));

toTop.addEventListener("click", () => window.scrollTo({ top: 0, behavior: "smooth" }));

const spyLinks = document.querySelectorAll(".menu a[data-link^='#']");
const spySections = [...spyLinks].map(a => document.querySelector(a.getAttribute("data-link"))).filter(Boolean);
if (spySections.length) {
  const spyObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        const id = "#" + entry.target.id;
        spyLinks.forEach(a => a.classList.toggle("active", a.getAttribute("data-link") === id));
      }
    });
  }, { rootMargin: "-45% 0px -50% 0px" });
  spySections.forEach(s => spyObserver.observe(s));
}

/* ---------- Reveal on scroll ---------- */
const revealEls = document.querySelectorAll(".reveal");
const revealObserver = new IntersectionObserver((entries) => {
  entries.forEach((entry, i) => {
    if (entry.isIntersecting) {
      const siblings = entry.target.parentElement ? [...entry.target.parentElement.children] : [];
      const idx = siblings.indexOf(entry.target);
      entry.target.style.transitionDelay = (Math.max(idx, 0) % 6) * 70 + "ms";
      entry.target.classList.add("in-view");
      revealObserver.unobserve(entry.target);
    }
  });
}, { threshold: 0.15 });
revealEls.forEach(el => revealObserver.observe(el));

/* ---------- Count-up stats ---------- */
const statEls = document.querySelectorAll(".stat .value[data-target]");
const statObserver = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (!entry.isIntersecting) return;
    const el = entry.target;
    const target = parseInt(el.dataset.target, 10) || 0;
    const duration = 1400;
    const start = performance.now();
    function tick(now) {
      const p = Math.min(1, (now - start) / duration);
      const eased = 1 - Math.pow(1 - p, 3);
      el.textContent = Math.round(target * eased).toLocaleString();
      if (p < 1) requestAnimationFrame(tick);
    }
    requestAnimationFrame(tick);
    statObserver.unobserve(el);
  });
}, { threshold: 0.4 });
statEls.forEach(el => statObserver.observe(el));

/* ---------- Card tilt ---------- */
document.querySelectorAll(".card").forEach(card => {
  card.addEventListener("pointermove", (e) => {
    if (e.pointerType === "touch") return;
    const r = card.getBoundingClientRect();
    const x = (e.clientX - r.left) / r.width - 0.5;
    const y = (e.clientY - r.top) / r.height - 0.5;
    card.style.transform = `perspective(700px) rotateX(${(-y * 6).toFixed(2)}deg) rotateY(${(x * 6).toFixed(2)}deg) translateY(-4px)`;
  });
  card.addEventListener("pointerleave", () => { card.style.transform = ""; });
});

/* ---------- Recharge console demo ---------- */
const networkRow = document.getElementById("networkRow");
const amountRow = document.getElementById("amountRow");
const consoleCta = document.getElementById("consoleCta");
const consoleStatus = document.getElementById("consoleStatus");
let selectedNetwork = "MTN", selectedAmount = "500", running = false;

networkRow.addEventListener("click", (e) => {
  const btn = e.target.closest(".network-chip");
  if (!btn) return;
  networkRow.querySelectorAll(".network-chip").forEach(b => b.classList.remove("active"));
  btn.classList.add("active");
  selectedNetwork = btn.dataset.network;
});

amountRow.addEventListener("click", (e) => {
  const btn = e.target.closest(".amount-chip");
  if (!btn) return;
  amountRow.querySelectorAll(".amount-chip").forEach(b => b.classList.remove("active"));
  btn.classList.add("active");
  selectedAmount = btn.dataset.amount;
});

consoleCta.addEventListener("click", () => {
  if (running) return;
  running = true;
  consoleCta.classList.add("running");
  consoleStatus.classList.remove("success");
  consoleStatus.textContent = `Sending ₦${Number(selectedAmount).toLocaleString()} to ${selectedNetwork}…`;

  setTimeout(() => {
    consoleCta.classList.remove("running");
    consoleStatus.classList.add("success");
    consoleStatus.innerHTML = `<span class="check"><i class="fa-solid fa-circle-check"></i></span> ₦${Number(selectedAmount).toLocaleString()} ${selectedNetwork} recharge delivered instantly.`;

    const track = document.getElementById("tickerTrack");
    const item = document.createElement("div");
    item.className = "ticker-item";
    item.innerHTML = `<span class="dot"></span><span><strong>You</strong> topped up ₦${Number(selectedAmount).toLocaleString()} ${selectedNetwork} airtime</span><span class="time">Just now</span>`;
    track.prepend(item);

    running = false;
  }, 1200);
});

/* ---------- Mobile app tabbed preview ---------- */
const appFeatureList = document.getElementById("appFeatureList");
const screenIcon = document.getElementById("screenIcon");
const screenLabel = document.getElementById("screenLabel");
const screenBig = document.getElementById("screenBig");
const screenSmall = document.getElementById("screenSmall");
const phoneScreen = document.getElementById("phoneScreen");

appFeatureList.addEventListener("click", (e) => {
  const btn = e.target.closest(".app-feature");
  if (!btn) return;
  appFeatureList.querySelectorAll(".app-feature").forEach(b => b.classList.remove("active"));
  btn.classList.add("active");

  phoneScreen.style.opacity = 0;
  setTimeout(() => {
    screenIcon.innerHTML = `<i class="fa-solid ${btn.dataset.icon}"></i>`;
    screenLabel.textContent = btn.dataset.label;
    screenBig.textContent = btn.dataset.big;
    screenSmall.textContent = btn.dataset.small;
    phoneScreen.style.transition = "opacity .25s ease";
    phoneScreen.style.opacity = 1;
  }, 150);
});
</script>

</body>
</html>
