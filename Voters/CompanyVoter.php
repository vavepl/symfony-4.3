<?php


namespace App\Security\Voters;


use App\Entity\Company;
use App\Entity\Employee;
use App\Exception\Messages;
use LogicException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class CompanyVoter extends Voter
{
    public const VIEW = 'company:view';
    public const EDIT = 'company:edit';

    private const ATTRIBUTES = [
        self::VIEW,
        self::EDIT,
    ];

    /**
     * @param string $attribute
     * @param mixed $subject
     * @return bool
     */
    protected function supports($attribute, $subject)
    {
        if (!in_array($attribute, self::ATTRIBUTES)) {
            return false;
        }

        return true;
    }

    /**
     * @param string $attribute
     * @param mixed $subject
     * @param TokenInterface $token
     * @return bool
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $employee = $token->getUser();
        if (!$employee instanceof Employee) {
            return false;
        }

        switch ($attribute) {
            case self::VIEW:
                return $this->canView($subject, $employee);
            case self::EDIT:
                return $this->canEdit($subject, $employee);
        }

        throw new LogicException(Messages::THIS_CODE_SHOULD_NOT_BE_REACHED);
    }

    /**
     * @param Company $company
     * @param Employee $employee
     * @return bool
     */
    private function canView(Company $company, Employee $employee)
    {
        return $company->getEmployees()->contains($employee);
    }

    /**
     * @param Company $company
     * @param Employee $employee
     * @return bool
     */
    private function canEdit(Company $company, Employee $employee)
    {
        return $company->getEmployees()->contains($employee)
            && in_array(Employee::ROLE_COMPANY_OWNER, $employee->getRoles());

    }
}
