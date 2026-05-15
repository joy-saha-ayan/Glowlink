from selenium import webdriver
from selenium.webdriver.common.by import By
from webdriver_manager.chrome import ChromeDriverManager
from selenium.webdriver.chrome.service import Service
from bs4 import BeautifulSoup
import mysql.connector
import time
import random
import re

# ==========================================
# DATABASE
# ==========================================
db_config = {
    "host": "localhost",
    "user": "root",
    "password": "",
    "database": "glowlinkp_db",
    "port": 3308
}

conn = mysql.connector.connect(**db_config)
cursor = conn.cursor()

print("✅ Database Connected!")

# ==========================================
# CHROME DRIVER
# ==========================================
options = webdriver.ChromeOptions()

options.add_argument("--start-maximized")
options.add_argument("--disable-blink-features=AutomationControlled")

driver = webdriver.Chrome(
    service=Service(ChromeDriverManager().install()),
    options=options
)

# ==========================================
# OPEN WEBSITE
# ==========================================
url = "https://www.themallbd.com/"

driver.get(url)

time.sleep(5)

# ==========================================
# SCROLL
# ==========================================
for i in range(10):

    driver.execute_script(
        "window.scrollTo(0, document.body.scrollHeight);"
    )

    time.sleep(3)

# ==========================================
# GET HTML
# ==========================================
soup = BeautifulSoup(driver.page_source, "html.parser")

# ==========================================
# FIND PRODUCT LINKS
# ==========================================
product_links = []

links = soup.select("a[href*='/product/']")

for item in links:

    href = item.get("href")

    if not href:
        continue

    if href.startswith("/"):

        href = "https://www.themallbd.com" + href

    if href not in product_links:

        product_links.append(href)

product_links = product_links[:100]

print(f"\n✅ Found {len(product_links)} Products\n")

# ==========================================
# INSERT QUERY
# ==========================================
insert_query = """
INSERT INTO products
(name, sku, price, stock, main_image_url, description, product_url, category)
VALUES (%s,%s,%s,%s,%s,%s,%s,%s)
"""

# ==========================================
# PRODUCT DETAILS
# ==========================================
success = 0

for link in product_links:

    try:

        driver.get(link)

        time.sleep(3)

        product_soup = BeautifulSoup(
            driver.page_source,
            "html.parser"
        )

        # NAME
        name = "Unknown Product"

        title = product_soup.select_one("h1")

        if title:
            name = title.get_text(strip=True)

        # PRICE
        price = 0.00

        price_tag = product_soup.select_one("span")

        if price_tag:

            text = price_tag.get_text(strip=True)

            text = re.sub(r"[^\d.]", "", text)

            try:
                price = float(text)
            except:
                price = 0.00

        # IMAGE
        image_url = ""

        img = product_soup.select_one("img")

        if img and img.get("src"):
            image_url = img["src"]

        # DESCRIPTION
        description = "No description"

        desc = product_soup.select_one("p")

        if desc:
            description = desc.get_text(strip=True)[:1000]

        # SKU
        sku = f"MALL-{random.randint(100000,999999)}"

        # STOCK
        stock = random.randint(20, 120)

        # DUPLICATE CHECK
        cursor.execute(
            "SELECT id FROM products WHERE name=%s",
            (name,)
        )

        exists = cursor.fetchone()

        if exists:
            print(f"⚠️ Duplicate Skipped: {name}")
            continue

        # INSERT
        cursor.execute(
            insert_query,
            (
                name,
                sku,
                price,
                stock,
                image_url,
                description,
                link,
                "Skin Care"
            )
        )

        conn.commit()

        success += 1

        print(f"✅ Added: {name} | ৳{price}")

    except Exception as e:

        print(f"❌ Error: {link}")
        print(e)

# ==========================================
# FINISH
# ==========================================
print(f"\n🎉 Finished! Total Added: {success}")

driver.quit()

cursor.close()
conn.close()