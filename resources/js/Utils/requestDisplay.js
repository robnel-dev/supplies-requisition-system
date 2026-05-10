export const formatRequestDepartment = (request) => {
    const departmentName = request.department?.name || '-';

    if (request.department?.type !== 'store') {
        return departmentName;
    }

    // Store-area requests show both the area department and the exact store account.
    const storeName = request.user?.external_department_reference?.name || request.user?.name;

    return storeName ? `${departmentName} - ${storeName}` : departmentName;
};
