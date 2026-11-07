{{-- Pictogrammes au trait (dessinés pour Tsukimori), utilisés par <x-picto name="…" />. --}}
<svg class="icon-sprite" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
    <defs>
        <path id="i-petale" d="M12 12C10.3 9.6 10.2 6.6 11.2 4.6L12 5.6L12.8 4.6C13.8 6.6 13.7 9.6 12 12Z"/>
        <path id="sb-petale" d="M0 0C-3.4-4.2-3.6-9.4-1.6-12.8L0-11L1.6-12.8C3.6-9.4 3.4-4.2 0 0Z"/>
        <g id="sb-fleur">
            <use href="#sb-petale"/>
            <use href="#sb-petale" transform="rotate(72)"/>
            <use href="#sb-petale" transform="rotate(144)"/>
            <use href="#sb-petale" transform="rotate(216)"/>
            <use href="#sb-petale" transform="rotate(288)"/>
        </g>
    </defs>
    <symbol id="i-torii" viewBox="0 0 24 24"><path d="M2.5 5.2C8.5 6.9 15.5 6.9 21.5 5.2M4.6 8.2H19.4M5.2 11.6H18.8M7.3 8.2L6.5 20.5M16.7 8.2L17.5 20.5M12 8.2V11.6"/></symbol>
    <symbol id="i-sakura" viewBox="0 0 24 24"><use href="#i-petale"/><use href="#i-petale" transform="rotate(72 12 12)"/><use href="#i-petale" transform="rotate(144 12 12)"/><use href="#i-petale" transform="rotate(216 12 12)"/><use href="#i-petale" transform="rotate(288 12 12)"/><circle cx="12" cy="12" r="1.1"/></symbol>
    <symbol id="i-lanterne" viewBox="0 0 24 24"><path d="M9.5 3.5H14.5M12 3.5V5.2M8.6 5.2H15.4C16.7 7 17.3 9.4 17.3 12C17.3 14.6 16.7 17 15.4 18.8H8.6C7.3 17 6.7 14.6 6.7 12C6.7 9.4 7.3 7 8.6 5.2ZM7 9.2H17M6.8 12H17.2M7 14.8H17M10 18.8V20.5H14V18.8"/></symbol>
    <symbol id="i-montagnes" viewBox="0 0 24 24"><path d="M2 19.5L8.6 8.5L12.4 14.5M10.8 12L14.8 5.8L22 19.5H2M7 11.2L8.6 12.4L10 11.2M13.3 8.2L14.8 9.4L16.3 8.2"/></symbol>
    <symbol id="i-lune" viewBox="0 0 24 24"><path d="M15.2 3.6A8.6 8.6 0 1 0 20.6 15.4A7 7 0 1 1 15.2 3.6Z"/></symbol>
    <symbol id="i-vagues" viewBox="0 0 24 24"><path d="M1.5 20A5 5 0 0 1 11.5 20M4 20A2.5 2.5 0 0 1 9 20M12.5 20A5 5 0 0 1 22.5 20M15 20A2.5 2.5 0 0 1 20 20M7 15A5 5 0 0 1 17 15M9.5 15A2.5 2.5 0 0 1 14.5 15"/></symbol>
    <symbol id="i-loupe" viewBox="0 0 24 24"><circle cx="10.5" cy="10.5" r="6"/><path d="M15 15L20.5 20.5"/></symbol>
    <symbol id="i-compte" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9.5"/><circle cx="12" cy="9.6" r="3.2"/><path d="M6.4 18.4C7.6 15.8 9.6 14.6 12 14.6C14.4 14.6 16.4 15.8 17.6 18.4"/></symbol>
    <symbol id="i-enveloppe" viewBox="0 0 24 24"><rect x="3" y="5.5" width="18" height="13" rx="1.5"/><path d="M3.6 6.6L12 13L20.4 6.6"/></symbol>
    <symbol id="i-fleche" viewBox="0 0 24 24"><path d="M4 12H19.5M14.5 7L19.5 12L14.5 17"/></symbol>
    <symbol id="i-chevron" viewBox="0 0 24 24"><path d="M9 5.5L15.5 12L9 18.5"/></symbol>
    <symbol id="i-bas" viewBox="0 0 24 24"><path d="M6 9.5L12 15.5L18 9.5"/></symbol>
    <symbol id="i-menu" viewBox="0 0 24 24"><path d="M4 7H20M4 12H20M4 17H20"/></symbol>
    <symbol id="i-fermer" viewBox="0 0 24 24"><path d="M6 6L18 18M18 6L6 18"/></symbol>
    <symbol id="i-plume" viewBox="0 0 24 24"><path d="M20 4C13.5 4.6 8.4 10 6.4 18.4L5.6 20.5M6.4 18.4C11.4 16.8 16 12.6 20 4M9.6 13.8H14.2"/></symbol>
    <symbol id="i-livre" viewBox="0 0 24 24"><path d="M12 6.6C9.6 5.1 6.6 4.6 3.5 5V18.6C6.6 18.2 9.6 18.7 12 20.2C14.4 18.7 17.4 18.2 20.5 18.6V5C17.4 4.6 14.4 5.1 12 6.6ZM12 6.6V20.2"/></symbol>
    <symbol id="i-dossier" viewBox="0 0 24 24"><path d="M3 6.5H9.4L11.4 8.8H21V18.8H3ZM3 10.8H21"/></symbol>
    <symbol id="i-journal" viewBox="0 0 24 24"><path d="M5 4.5H17V19.5H6.5A1.5 1.5 0 0 1 5 18ZM17 8.5H19.5V18A1.5 1.5 0 0 1 18 19.5H17M8 8H14M8 11H14M8 14H12"/></symbol>
    <symbol id="i-murmure" viewBox="0 0 24 24"><path d="M4.5 6.5C4.5 5.4 5.4 4.5 6.5 4.5H17.5C18.6 4.5 19.5 5.4 19.5 6.5V13.5C19.5 14.6 18.6 15.5 17.5 15.5H11L7 19V15.5H6.5C5.4 15.5 4.5 14.6 4.5 13.5ZM8.5 10H8.6M12 10H12.1M15.5 10H15.6"/></symbol>
    <symbol id="i-maison" viewBox="0 0 24 24"><path d="M2.8 10.4L12 4L21.2 10.4M5 9.2V19.5H19V9.2M10 19.5V14H14V19.5"/></symbol>
    <symbol id="i-parchemin" viewBox="0 0 24 24"><path d="M7 4.5H18.5V17A2.5 2.5 0 0 1 16 19.5H6.5M7 4.5A2 2 0 0 0 5 6.5V8H7M7 4.5A2 2 0 0 1 9 6.5V17A2.5 2.5 0 0 1 6.5 19.5A2.5 2.5 0 0 1 4 17V16H9M11.5 8.5H16M11.5 11.5H16"/></symbol>
    <symbol id="i-calendrier" viewBox="0 0 24 24"><rect x="3.5" y="5.5" width="17" height="15" rx="1.5"/><path d="M3.5 9.5H20.5M8 3.5V7M16 3.5V7"/></symbol>
    <symbol id="i-personnes" viewBox="0 0 24 24"><circle cx="9" cy="8.5" r="3"/><path d="M3.5 19C4.3 15.6 6.4 14 9 14C11.6 14 13.7 15.6 14.5 19"/><circle cx="16.5" cy="9.5" r="2.5"/><path d="M15.6 14.2C18 14.2 19.8 15.6 20.5 18.5"/></symbol>
    <symbol id="i-repere" viewBox="0 0 24 24"><path d="M12 21C12 21 5.5 14.5 5.5 10A6.5 6.5 0 0 1 18.5 10C18.5 14.5 12 21 12 21Z"/><circle cx="12" cy="10" r="2.3"/></symbol>
    <symbol id="i-noeud" viewBox="0 0 24 24"><path d="M12 8.5L15 11.5L12 14.5L9 11.5ZM9 11.5C6.2 9.4 4.6 12.6 7.2 12.8M15 11.5C17.8 9.4 19.4 12.6 16.8 12.8"/></symbol>
    <symbol id="i-masque" viewBox="0 0 24 24"><path d="M5 5.5C9.4 3.8 14.6 3.8 19 5.5C19.4 12.4 16.6 18.4 12 20C7.4 18.4 4.6 12.4 5 5.5ZM8.4 10.4C9.2 9.8 10.2 9.8 11 10.4M13 10.4C13.8 9.8 14.8 9.8 15.6 10.4M10 15C11.3 15.8 12.7 15.8 14 15"/></symbol>
</svg>
