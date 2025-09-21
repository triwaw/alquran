<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Quran Scraper</title>
  <style>
    body { font-family: Arial, sans-serif; padding: 20px; }
    label { display: block; margin-top: 12px; }
    input, select { padding: 6px; width: 300px; }
    button { margin-top: 15px; padding: 10px 20px; cursor: pointer; }
  </style>
</head>
<body>
  <h2>Quran Scraper to Excel</h2>
  <form action="scrape_quran_excel.php" method="post">
    <label>
      URL:
      <input type="text" name="url" value="https://tanzil.net/pub/sample/show-sura.php?sura=" required>
    </label>

    <label>
      Delay between requests (ms):
      <select name="delay">
        <option value="1000">1 sec</option>
        <option value="3000">3 sec</option>
        <option value="5000" selected>5 sec</option>
        <option value="7000">7 sec</option>
        <option value="9000">9 sec</option>
        <option value="10000">10 sec</option>
      </select>
    </label>

    <label>
      Start Sura:
      <input type="number" name="start" min="1" max="114" value="1">
    </label>

    <label>
      End Sura:
      <input type="number" name="end" min="1" max="114" value="114">
    </label>

    <button type="submit">Start Scraping</button>
  </form>
</body>
</html>
