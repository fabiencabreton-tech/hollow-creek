{{-- Champ piège : laissé vide par les personnes, rempli par les robots. --}}
<div class="hp-field" aria-hidden="true">
    <label for="site_web">Ne pas remplir ce champ</label>
    <input type="text" id="site_web" name="{{ \App\Http\Middleware\RejectHoneypotSubmissions::FIELD }}" value="" tabindex="-1" autocomplete="off">
</div>
