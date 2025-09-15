<h1>Surah {{ $surah->number }} - {{ $surah->arabic_name }}</h1>

@foreach($verses as $verse)
    <div style="margin-bottom:20px; padding:10px; border-bottom:1px solid #ccc;">
        <p><strong>Ayah {{ $verse->verse_number }}</strong></p>
        <p style="font-size:20px; direction:rtl;">{{ $verse->text_arabic }}</p>

        @foreach($verse->translations as $tr)
            <p><em>[{{ strtoupper($tr->language) }} - {{ $tr->translator }}]</em>: {{ $tr->text }}</p>
        @endforeach
    </div>
@endforeach

<a href="{{ route('quran.index') }}">🔙 Search another Surah</a>
