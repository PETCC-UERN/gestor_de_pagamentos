import { useState } from 'react'

export default function FaturaForm({ onSalvar }) {
    const [form, setForm] = useState({
        clienteId: '',
        valor: '',
        vencimento: '',
        status: 'PENDENTE',
    })

    function handleChange(e) {
        setForm({ ...form, [e.target.name]: e.target.value })
    }

    function handleSubmit(e) {
        e.preventDefault()
        onSalvar({
            cliente: { id: Number(form.clienteId) },
            valor: Number(form.valor),
            vencimento: form.vencimento,
            status: form.status,
        })
        setForm({ clienteId: '', valor: '', vencimento: '', status: 'PENDENTE' })
    }

    return (
        <form onSubmit={handleSubmit} className="flex flex-col gap-3">
            <input
                name="clienteId"
                value={form.clienteId}
                onChange={handleChange}
                placeholder="ID do Cliente"
                type="number"
                className="border rounded p-2"
                required
            />
            <input
                name="valor"
                value={form.valor}
                onChange={handleChange}
                placeholder="Valor"
                type="number"
                step="0.01"
                className="border rounded p-2"
                required
            />
            <input
                name="vencimento"
                value={form.vencimento}
                onChange={handleChange}
                type="date"
                className="border rounded p-2"
                required
            />
            <select
                name="status"
                value={form.status}
                onChange={handleChange}
                className="border rounded p-2"
            >
                <option value="PENDENTE">Pendente</option>
                <option value="PAGO">Pago</option>
                <option value="ATRASADO">Atrasado</option>
            </select>
            <button
                type="submit"
                className="bg-blue-600 text-white rounded p-2 hover:bg-blue-700"
            >
                Salvar
            </button>
        </form>
    )
}