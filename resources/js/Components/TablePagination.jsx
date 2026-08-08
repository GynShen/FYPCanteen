export const TABLE_PAGE_SIZE = 7;

export default function TablePagination({
    totalItems,
    currentPage,
    onPageChange,
}) {
    const totalPages = Math.max(1, Math.ceil(totalItems / TABLE_PAGE_SIZE));

    if (totalItems <= TABLE_PAGE_SIZE) return null;

    return (
        <div className="flex flex-col gap-3 border-t border-gray-200 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
            <p className="text-sm text-gray-600 dark:text-gray-300">
                Showing {(currentPage - 1) * TABLE_PAGE_SIZE + 1}–{Math.min(currentPage * TABLE_PAGE_SIZE, totalItems)} of {totalItems}
            </p>
            <div className="flex items-center gap-3">
                <button type="button" onClick={() => onPageChange(currentPage - 1)} disabled={currentPage === 1} className="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 disabled:cursor-not-allowed disabled:opacity-40">Previous</button>
                <span className="text-sm font-medium text-gray-700 dark:text-gray-200">Page {currentPage} of {totalPages}</span>
                <button type="button" onClick={() => onPageChange(currentPage + 1)} disabled={currentPage === totalPages} className="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 disabled:cursor-not-allowed disabled:opacity-40">Next</button>
            </div>
        </div>
    );
}
