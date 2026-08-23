import { Link } from 'react-router-dom'

export default function Impressum() {
  return (
    <div className="mx-auto max-w-2xl p-8">
      <h1 className="mb-6 text-2xl font-bold">Impressum</h1>

      <h2 className="mb-2 text-lg font-semibold">Angaben gemäß § 5 DDG</h2>
      <p className="mb-4">
        Daniel Schwabe<br />
        Paul-Lange-Bey-Straße 28<br />
        14476 Potsdam
      </p>

      <h2 className="mb-2 text-lg font-semibold">Kontakt</h2>
      <p className="mb-4">
        E-Mail: <a href="mailto:h1596-acroyoga_imprint@yahoo.com" className="underline">h1596-acroyoga_imprint@yahoo.com</a>
      </p>

      <h2 className="mb-2 text-lg font-semibold">Verantwortlich für den Inhalt</h2>
      <p className="mb-4">
        Daniel Schwabe, Anschrift wie oben.<br />
        Dieses Angebot wird privat und nicht geschäftsmäßig betrieben.
      </p>

      <p className="mt-8 text-sm">
        <Link to="/datenschutz" className="underline">Datenschutzerklärung</Link>
      </p>
    </div>
  )
}
