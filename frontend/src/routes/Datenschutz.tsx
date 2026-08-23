import { Link } from 'react-router-dom'

export default function Datenschutz() {
  return (
    <div className="mx-auto max-w-2xl p-8">
      <h1 className="mb-6 text-2xl font-bold">Datenschutzerklärung</h1>

      <h2 className="mb-2 text-lg font-semibold">1. Verantwortlicher</h2>
      <p className="mb-4">
        Verantwortlich für die Datenverarbeitung auf dieser Website ist:<br />
        Daniel Schwabe<br />
        Paul-Lange-Bey-Straße 28, 14476 Potsdam<br />
        E-Mail: <a href="mailto:schwabe.daniel@yahoo.de" className="underline">schwabe.daniel@yahoo.de</a>
      </p>

      <h2 className="mb-2 text-lg font-semibold">2. Server-Logfiles</h2>
      <p className="mb-4">
        Beim Aufruf dieser Website werden durch den Hosting-Provider automatisch
        Informationen in sogenannten Server-Logfiles erfasst, die dein Browser
        übermittelt. Dies sind: IP-Adresse, Datum und Uhrzeit des Zugriffs,
        aufgerufene Seite, verwendeter Browser und Betriebssystem. Diese Daten
        sind technisch erforderlich, um die Website auszuliefern, ihre Stabilität
        und Sicherheit zu gewährleisten. Rechtsgrundlage ist Art. 6 Abs. 1 lit. f
        DSGVO (berechtigtes Interesse am sicheren und störungsfreien Betrieb).
        Die Logfiles werden nach spätestens 7 Tagen gelöscht, sofern keine
        sicherheitsrelevanten Vorfälle eine längere Aufbewahrung erfordern.
      </p>

      <h2 className="mb-2 text-lg font-semibold">3. Keine weitere Datenverarbeitung</h2>
      <p className="mb-4">
        Diese Website befindet sich derzeit im Wartungsmodus. Es werden keine
        Cookies gesetzt, kein Tracking und keine Analyse-Werkzeuge eingesetzt,
        und es findet keine Registrierung oder sonstige Erhebung personenbezogener
        Daten über Eingabeformulare statt.
      </p>

      <h2 className="mb-2 text-lg font-semibold">4. Deine Rechte</h2>
      <p className="mb-4">
        Du hast das Recht auf Auskunft (Art. 15 DSGVO), Berichtigung (Art. 16),
        Löschung (Art. 17), Einschränkung der Verarbeitung (Art. 18) sowie
        Widerspruch gegen die Verarbeitung (Art. 21). Zudem steht dir ein
        Beschwerderecht bei einer Datenschutz-Aufsichtsbehörde zu. Für Anfragen
        wende dich an die oben genannte E-Mail-Adresse.
      </p>

      <h2 className="mb-2 text-lg font-semibold">5. SSL-/TLS-Verschlüsselung</h2>
      <p className="mb-4">
        Diese Website nutzt aus Sicherheitsgründen eine TLS-Verschlüsselung. Eine
        verschlüsselte Verbindung erkennst du am „https://“ in der Adresszeile
        deines Browsers.
      </p>

      <p className="mt-8 text-sm">
        <Link to="/impressum" className="underline">Impressum</Link>
      </p>
    </div>
  )
}
