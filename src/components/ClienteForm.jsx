import { useState } from 'react'

export default function ClienteForm({ onSalvar }) {
    const [form, setForm] = useState({ nome: '', email: '', telefone: '' })

    function handleChange(e) {
        setForm({ ...form, [e.target.name]: e.target.value })
    }

    function handleSubmit(e) {
        e.preventDefault()
        onSalvar(form)
        setForm({ nome: '', email: '', telefone: '' })
    }

    return (
        <form onSubmit={handleSubmit} className="flex flex-col gap-3">
            <input
                name="nome"
                value={form.nome}
                onChange={handleChange}
                placeholder="Nome"
                className="border rounded p-2"
                required
            />
            <input
                name="email"
                value={form.email}
                onChange={handleChange}
                placeholder="Email"
                type="email"
                className="border rounded p-2"
                required
            />
            <input
                name="telefone"
                value={form.telefone}
                onChange={handleChange}
                placeholder="Telefone"
                className="border rounded p-2"
            />
            <button
                type="submit"
                className="bg-blue-600 text-white rounded p-2 hover:bg-blue-700"
            >
                Salvar
            </button>
        </form>
    )
}