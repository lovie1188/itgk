# Pending Task Documentation: Ad Network Monetization Setup

> **Status:** Pending Configuration (Target: Future Release upon Ad Account Approval)  
> **Module Affected:** Certificate Acknowledgement Page ([`app/Views/pages/certificate/acknowledgement.php`](file:///D:/xampp/htdocs/certificate/app/Views/pages/certificate/acknowledgement.php))

---

## 📌 Task Summary
The frontend containers for Ad Monetization have been implemented cleanly and dynamically in `acknowledgement.php`. Empty containers are hidden (`display:none;`) by default and will automatically expand only when an ad is successfully filled.

The actual Ad Network Account IDs, Client Publisher Keys, and Slot IDs need to be configured when the domain (`softtechseva.com` or production domain) is approved by the ad network provider.

---

## ⚙️ Configuration Setup Guide

### 1. Google AdSense Integration (Web Display Banner)
- **Prerequisites:** Approved Google AdSense account for production domain.
- **Target File:** [`app/Views/pages/certificate/acknowledgement.php`](file:///D:/xampp/htdocs/certificate/app/Views/pages/certificate/acknowledgement.php)
- **Top Slot (`#ad-slot-top-wrapper`):**
  - Replace `ca-pub-XXXXXXXXXXXXXXXX` with valid AdSense Publisher ID.
  - Replace `data-ad-slot="1234567890"` with AdSense Top Display Banner Slot ID.
- **Bottom Slot (`#ad-slot-bottom-wrapper`):**
  - Replace `ca-pub-XXXXXXXXXXXXXXXX` with valid AdSense Publisher ID.
  - Replace `data-ad-slot="0987654321"` with AdSense Bottom Display Banner Slot ID.

### 2. Meta Audience Network (Facebook Ads)
- **Prerequisites:** Approved Meta Business Placement ID.
- **Implementation:** Replace the `<ins class="adsbygoogle">` tag inside `#ad-slot-top-wrapper` or `#ad-slot-bottom-wrapper` with Meta Audience Network JavaScript SDK snippet.

### 3. Google AdMob (Hybrid / Mobile Webview App)
- **Prerequisites:** AdMob App ID & Ad Unit ID for Android / iOS WebView integration.
- **Implementation:** Bind Webview JS bridge or place AdMob web banner unit code inside the wrapper elements.

---

## 📝 Checklist for Activation
- [ ] Obtain Google AdSense Domain Approval for Production Environment.
- [ ] Create 2 Display Ad Units (Responsive Horizontal Banners) in AdSense Dashboard.
- [ ] Replace `ca-pub-XXXXXXXXXXXXXXXX` with live Publisher ID in `acknowledgement.php` or `.env` configuration.
- [ ] Replace Ad Slot IDs (`1234567890` & `0987654321`) with live Ad Unit Slot IDs.
- [ ] Verify that ad containers remain completely invisible if ads fail to fill or load.
