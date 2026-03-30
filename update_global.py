import os
import glob
import re

new_footer = """  <!-- Footer -->
  <footer class="footer">
    <div class="footer-top">
      <div class="footer-brand">
        <img src="images/gradient_logo.svg" alt="Ahmed Malik Logo" class="footer-logo-img" style="height:40px; margin-bottom:1rem;" />
        <p class="footer-tagline">Lets talk together</p>
        <form class="newsletter-form" onsubmit="return false;">
          <input type="email" placeholder="Enter your email" class="newsletter-input" />
          <button type="submit" class="newsletter-btn">Let me know</button>
        </form>
        <div style="margin-top: 2rem;">
          <a href="contact.html" class="newsletter-btn" style="text-decoration:none; display:inline-block; font-family:var(--font-mono);">Contact Now &#8594;</a>
        </div>
      </div>
      <div class="footer-links">
        <div class="footer-col">
          <p class="footer-col-title">Features</p>
          <a href="about.html" class="footer-link">About</a>
          <a href="work.html" class="footer-link">Work</a>
          <a href="certifications.html" class="footer-link">Certifications</a>
          <a href="blog.html" class="footer-link">Blog</a>
          <a href="contact.html" class="footer-link">Contact</a>
        </div>
        <div class="footer-col">
          <p class="footer-col-title">Company</p>
          <a href="https://tamxai.com" target="_blank" rel="noopener noreferrer" class="footer-link">TAMx</a>
          <a href="#" class="footer-link">QuickSilver</a>
        </div>
      </div>
    </div>
    <div class="footer-wordmark">AHMED MALIK</div>
    <div class="footer-bottom">
      <p class="footer-copyright">2026 Â© Ahmed Malik Copyright</p>
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
    content = content.replace('href="#about"', 'href="about.html"')
    content = content.replace('href="#work"', 'href="work.html"')
    content = content.replace('href="index.html#about"', 'href="about.html"')
    content = content.replace('href="index.html#work"', 'href="work.html"')

    with open(file, 'w', encoding='utf-8') as f:
        f.write(content)

    print(f"Updated {file}")



