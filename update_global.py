import os
import glob
import re

new_footer = """  <!-- Footer -->
  <footer class="footer">
    <div class="footer-top">
      <div class="footer-brand">
        <img src="images/gradient_logo.svg" alt="Ahmed Malik Logo" class="footer-logo-img" style="height:40px; margin-bottom:1rem;" />
        <p class="footer-tagline">Get weekly news on itsahmedmalik</p>
        <form class="newsletter-form" onsubmit="return false;">
          <input type="email" placeholder="Enter your email" class="newsletter-input" />
          <button type="submit" class="newsletter-btn">Subscribe</button>
        </form>
        <div style="margin-top: 2rem;">
          <a href="contact.html" class="newsletter-btn" style="text-decoration:none; display:inline-block; font-family:var(--font-mono);">Schedule a meeting ↗</a>
        </div>
      </div>
      <div class="footer-links">
        <div class="footer-col">
          <p class="footer-col-title">Features</p>
          <!-- Absolute Links so they work from any subpage -->
          <a href="index.html#about" class="footer-link">About</a>
          <a href="index.html#work" class="footer-link">Work</a>
          <a href="certifications.html" class="footer-link">Certifications</a>
          <a href="blog.html" class="footer-link">Blog</a>
          <a href="contact.html" class="footer-link">Contact</a>
        </div>
        <div class="footer-col">
          <p class="footer-col-title">Company</p>
          <a href="https://tamxai.com" target="_blank" rel="noopener noreferrer" class="footer-link">TAMx</a>
          <a href="#" class="footer-link">QuickSliver</a>
        </div>
      </div>
    </div>
    <div class="footer-wordmark">AHMED MALIK</div>
    <div class="footer-bottom">
      <p class="footer-copyright">2026 © Ahmed Malik Copyright</p>
    </div>
  </footer>"""

html_files = glob.glob("*.html")

for file in html_files:
    with open(file, 'r', encoding='utf-8') as f:
        content = f.read()

    # Use regex to find and replace the footer block. 
    # Works for both <footer id="contact" class="footer"> and <footer class="footer">
    content = re.sub(r'<!-- Footer -->\s*<footer.*?</footer>', new_footer, content, flags=re.DOTALL)

    # Update nav links
    content = content.replace('href="#contact"', 'href="contact.html"')
    content = content.replace('href="index.html#contact"', 'href="contact.html"')

    with open(file, 'w', encoding='utf-8') as f:
        f.write(content)

    print(f"Updated {file}")
