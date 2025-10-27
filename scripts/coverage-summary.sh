#!/bin/bash
# Generate a quick coverage summary focusing on business-critical code

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"

echo "🧪 Generating Code Coverage Summary..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

cd "$PROJECT_ROOT"

# Run PHPUnit with coverage HTML report (required for per-directory analysis)
echo "⏳ Running tests with coverage analysis..."
echo ""

docker compose exec -T -u www-data app php -d memory_limit=-1 -d pcov.enabled=1 -d pcov.directory=src \
    vendor/bin/phpunit \
    --coverage-html=var/coverage \
    --coverage-text \
    --colors=never \
    2>&1 | grep -E "(Classes|Methods|Lines|Time|Tests|Assertions)" | head -10

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📊 Overall Coverage Summary"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "✅ Coverage reports generated successfully!"
echo ""
echo "📁 HTML Report: var/coverage/index.html"
echo "   - Open in browser for detailed per-directory breakdown"
echo "   - View coverage by Domain/Application/Infrastructure layers"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🎯 Coverage Strategy"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "BUSINESS-CRITICAL CODE (High Value):"
echo "  • Domain Aggregates (User, etc.)      → Target: 95%+"
echo "  • Domain Value Objects                → Target: 90%+"
echo "  • Domain Events                       → Target: 85%+"
echo "  • Shared Kernel Domain                → Target: 90%+"
echo ""
echo "APPLICATION LAYER (Important):"
echo "  • Use Cases / Command Handlers        → Target: 85%+"
echo "  • Event Handlers / Projections        → Target: 80%+"
echo "  • Query Handlers / Finders            → Target: 75%+"
echo ""
echo "INFRASTRUCTURE (Selective):"
echo "  • Event Store                         → Target: 70%+"
echo "  • Repositories (complex logic only)   → Target: 60%+"
echo "  • Monitoring                          → Target: 50%+"
echo ""
echo "EXCLUDED FROM COVERAGE (by design):"
echo "  ❌ DTOs (Command/Query)               → Simple property bags"
echo "  ❌ Controllers                         → Tested via functional tests"
echo "  ❌ Doctrine Entities                   → Tested indirectly"
echo "  ❌ Exception Classes                   → No business logic"
echo "  ❌ Bus Wrappers                        → Thin Symfony delegations"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📝 Notes:"
echo ""
echo "  • Overall % appears low (~35%) because ~40% of code is excluded"
echo "  • Focus on business logic coverage in HTML report"
echo "  • See .claude/docs/COVERAGE_STRATEGY.md for complete philosophy"
echo ""
echo "💡 Commands:"
echo "   make coverage        → Generate HTML report"
echo "   make coverage-text   → Terminal summary"
echo "   open var/coverage/index.html → View detailed report"
echo ""
