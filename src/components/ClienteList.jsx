export default function ClienteList({ clientes, onDeletar }) {
    return (
        <div className="mt-6">
            {clientes.length === 0 && (
                <p className="text-gray-500">Nenhum cliente cadastrado.</p>
            )}
            {clientes.map((cliente) => (
                <div
                    key={cliente.id}
                    className="flex justify-between items-center border rounded p-4 mb-2"
                >
                    <div>
                        <p className="font-semibold">{cliente.nome}</p>
                        <p className="text-sm text-gray-500">{cliente.email}</p>
                    </div>
                    <button
                        onClick={() => onDeletar(cliente.id)}
                        className="text-red-500 hover:text-red-700 text-sm"
                    >
                        Remover
                    </button>
                </div>
            ))}
        </div>
    )
}