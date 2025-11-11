export default function AppLogo() {
    return (
        <>
            <div className="ml-1 grid flex-1 text-left text-sm">
                <img
                    src="/logo-light.png"
                    alt="Logo RatioView Light"
                    className="block h-auto w-full transition-opacity duration-300 dark:hidden"
                />
                <img
                    src="/logo-dark.png"
                    alt="Logo RatioView Dark"
                    className="hidden h-auto w-full transition-opacity duration-300 dark:block"
                />
            </div>
        </>
    );
}
