
export default function AppLogo() {
    return (
        <>
            <div className="ml-1 grid flex-1 text-left text-sm">
                <img
                    src="../logo-light.png"
                    alt="Logo RatioView Light"
                    className="block dark:hidden w-full h-auto transition-opacity duration-300"
                />
                <img
                    src="../logo-dark.png"
                    alt="Logo RatioView Dark"
                    className="hidden dark:block w-full h-auto transition-opacity duration-300"
                />
            </div>
        </>
    );
}