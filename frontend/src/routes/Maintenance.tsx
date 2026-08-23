import { Link } from 'react-router-dom'

export default function Maintenance() {
  return (
    <div className="mx-auto flex min-h-screen max-w-lg flex-col items-center justify-center gap-4 p-8 text-center">
      <h1 className="text-3xl font-bold">Wartungsarbeiten</h1>
      <p className="text-gray-600">
        Diese Seite befindet sich im Aufbau und ist derzeit nicht verfügbar.
        Wir sind bald wieder für dich da.
      </p>
      <p className="text-gray-600">This site is under construction. We&apos;ll be back soon.</p>
      <footer className="mt-6 flex gap-4 text-sm">
        <Link to="/impressum" className="underline">Impressum</Link>
        <Link to="/datenschutz" className="underline">Datenschutzerklärung</Link>
      </footer>
    </div>
  )
}
