import { useEffect, useState } from 'react'
import api from '../api/axios'
import ClienteForm from '../components/ClienteForm'
import ClienteList from '../components/ClienteList'

export default function Clientes() {
    const [clientes, setClientes] = useState([])

    async function carregar() {
        const response = await api.get('/clientes')
        setClientes(response.data)
    }

    async function salvar(dados) {
        await api.post('/clientes', dados)
        carregar()
    }

    async function deletar(id) {
        await api.delete(`/clientes/${id}`)
        carregar()
    }

    useEffect(() => {
        carregar()
    }, [])

    return (
        <div className="max-w-2xl mx-auto p-6">
            <h1 className="text-2xl font-bold mb-4">Clientes</h1>
            <ClienteForm onSalvar={salvar} />
            <ClienteList clientes={clientes} onDeletar={deletar} />
        </div>
    )
}