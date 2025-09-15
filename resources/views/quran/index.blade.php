<h1>Search Surah</h1>

<form action="{{ route('quran.show') }}" method="get">
    <label for="surah">Enter Surah Number:</label>
    <input type="number" name="surah" id="surah" min="1" max="114" required>
    <button type="submit">Show Surah</button>
</form>
