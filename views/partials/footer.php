  <footer class="footer">
    <div class="footer-top">
      <div class="footer-brand">
        <img src="images/gradient_logo.svg" alt="Ahmed Malik Logo" class="footer-logo-img" style="height:40px; margin-bottom:1rem;" />
        <p class="footer-tagline">Get weekly news on itsahmedmalik.com</p>
        <form class="newsletter-form" action="/api/newsletter.php" method="post">
          <label class="sr-only" for="newsletter-email-<?= e($footerId) ?>">Email address</label>
          <input type="email" id="newsletter-email-<?= e($footerId) ?>" name="email" placeholder="Enter your email" class="newsletter-input" autocomplete="email" maxlength="254" required />
          <input type="hidden" name="source_path" value="<?= e($sourcePath) ?>" />
          <div class="honeypot-field" aria-hidden="true">
            <label>Website <input type="text" name="website" tabindex="-1" autocomplete="off" /></label>
          </div>
          <div class="turnstile-widget" data-action="newsletter" data-turnstile-action="newsletter"></div>
          <button type="submit" class="newsletter-btn">Let me know</button>
          <p class="form-status" data-form-status aria-live="polite"></p>
        </form>
        <div style="margin-top: 2rem;">
          <a href="contact.html" class="newsletter-btn" style="text-decoration:none; display:inline-block; font-family:var(--font-main);">Contact Now &#8594;</a>
        </div>
      </div>
      <div class="footer-links">
        <div class="footer-col">
          <p class="footer-col-title">Features</p>
          <a href="about.html" class="footer-link">About</a>
          <a href="work.html" class="footer-link">Work</a>
          <a href="certifications.html" class="footer-link">Certifications</a>
          <a href="events.html" class="footer-link">Events</a>
          <a href="blog.html" class="footer-link">Blog</a>
          <a href="contact.html" class="footer-link">Contact</a>
        </div>
        <div class="footer-col">
          <p class="footer-col-title">Company</p>
          <a href="https://tamxai.com" target="_blank" rel="noopener noreferrer" class="footer-link">TAMx</a>
          <span class="footer-link">QuickSilver</span>
        </div>
      </div>
    </div>
    <div class="footer-wordmark">AHMED MALIK</div>
    <div class="footer-bottom">
      <p class="footer-copyright">2026 &copy; Ahmed Malik Copyright</p>
    </div>
  </footer>
