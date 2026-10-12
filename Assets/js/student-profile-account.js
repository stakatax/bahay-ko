document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('[data-placement-form]');
    if (!form) return;
    const division = form.elements.department_id;
    const education = form.elements.education_level_id;
    const program = form.elements.academic_program_id;
    const grade = form.elements.grade_level_id;
    const section = form.elements.section_id;
    const restrict = (select, predicate) => {
        Array.from(select.options).forEach((option) => {
            const eligible = !option.value || predicate(option);
            option.hidden = !eligible;
            option.disabled = !eligible;
        });
        if (select.selectedOptions[0]?.disabled) select.value = '';
    };
    const sync = () => {
        restrict(education, (option) => option.dataset.division === division.value);
        const level = education.value;
        restrict(program, (option) => option.dataset.level === level);
        const hasPrograms = Array.from(program.options).some((option) => option.value && !option.disabled);
        program.required = hasPrograms;
        program.options[0].textContent = hasPrograms ? 'Choose program or strand' : 'Not applicable';
        restrict(grade, (option) => option.dataset.level === level);
        restrict(section, (option) => option.dataset.grade === grade.value && Number(option.dataset.program) === Number(program.value));
        const confirmedUnchanged = form.querySelector('[data-placement-change]').value === 'same';
        const complete = [division, education, grade, section].every((select) => select.value)
            && (!hasPrograms || program.value);
        form.querySelector('[data-placement-fields]').hidden = confirmedUnchanged && Boolean(complete);
    };
    [division, education, program, grade, section].forEach((select) => select.addEventListener('change', () => {
        form.querySelector('[data-placement-change]').value = 'change';
        sync();
    }));
    form.querySelector('[data-placement-change]').addEventListener('change', (event) => {
        if (event.target.value === 'same') {
            [division, education, program, grade, section].forEach((select) => {
                select.value = select.dataset.current || '';
            });
        }
        sync();
    });
    sync();
    form.addEventListener('submit', (event) => {
        if (form.dataset.submitting) { event.preventDefault(); return; }
        form.dataset.submitting = 'true';
        form.querySelector('button[type="submit"]').disabled = true;
    });
});
